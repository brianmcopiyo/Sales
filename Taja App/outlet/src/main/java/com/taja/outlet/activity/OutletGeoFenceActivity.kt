package com.taja.outlet.activity

import android.Manifest
import android.content.Context
import android.content.pm.PackageManager
import android.location.LocationManager
import android.os.Bundle
import android.view.View
import android.widget.Button
import android.widget.EditText
import android.widget.ProgressBar
import android.widget.Spinner
import android.widget.TextView
import android.widget.Toast
import androidx.activity.result.contract.ActivityResultContracts
import androidx.appcompat.app.AppCompatActivity
import androidx.core.content.ContextCompat
import com.taja.outlet.ApiClient
import com.taja.outlet.R
import com.taja.outlet.SessionManager
import org.json.JSONArray
import org.json.JSONObject
import org.osmdroid.config.Configuration
import org.osmdroid.events.MapEventsReceiver
import org.osmdroid.tileprovider.tilesource.TileSourceFactory
import org.osmdroid.util.GeoPoint
import org.osmdroid.views.MapView
import org.osmdroid.views.overlay.MapEventsOverlay
import org.osmdroid.views.overlay.Marker
import org.osmdroid.views.overlay.Polygon

class OutletGeoFenceActivity : AppCompatActivity() {

    companion object {
        const val EXTRA_OUTLET_ID = "outlet_id"
    }

    private lateinit var sessionManager: SessionManager
    private lateinit var outletId: String
    private var loadedOutlet: ApiClient.Outlet? = null
    private var currentLat: Double? = null
    private var currentLng: Double? = null

    private lateinit var progressBar: ProgressBar
    private lateinit var typeSpinner: Spinner
    private lateinit var locationSection: View
    private lateinit var locationText: TextView
    private lateinit var useCurrentButton: Button
    private lateinit var radiusSection: View
    private lateinit var radiusEdit: EditText
    private lateinit var polygonSection: View
    private lateinit var mapView: MapView
    private lateinit var clearPointsButton: Button
    private lateinit var saveButton: Button
    private val polygonPoints = mutableListOf<GeoPoint>()
    private val locationPermissionLauncher = registerForActivityResult(
        ActivityResultContracts.RequestPermission()
    ) { granted ->
        if (granted) {
            fetchLastKnownLocation()
        } else {
            Toast.makeText(this, R.string.error_location_permission, Toast.LENGTH_SHORT).show()
        }
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        Configuration.getInstance().userAgentValue = packageName
        setContentView(R.layout.activity_outlet_geofence)
        sessionManager = SessionManager(this)
        if (!sessionManager.isLoggedIn) {
            Toast.makeText(this, R.string.error_session, Toast.LENGTH_SHORT).show()
            finish()
            return
        }
        outletId = intent.getStringExtra(EXTRA_OUTLET_ID).orEmpty()
        if (outletId.isBlank()) {
            Toast.makeText(this, R.string.error_loading_outlets, Toast.LENGTH_SHORT).show()
            finish()
            return
        }

        progressBar = findViewById(R.id.outlet_geofence_progress)
        typeSpinner = findViewById(R.id.outlet_geofence_type)
        locationSection = findViewById(R.id.outlet_geofence_location_section)
        locationText = findViewById(R.id.outlet_geofence_location_value)
        useCurrentButton = findViewById(R.id.outlet_geofence_use_current)
        radiusSection = findViewById(R.id.outlet_geofence_radius_section)
        radiusEdit = findViewById(R.id.outlet_geofence_radius)
        polygonSection = findViewById(R.id.outlet_geofence_polygon_section)
        mapView = findViewById(R.id.outlet_geofence_map)
        clearPointsButton = findViewById(R.id.outlet_geofence_clear_points)
        saveButton = findViewById(R.id.outlet_geofence_save)
        setupMap()

        findViewById<Button>(R.id.outlet_geofence_back).setOnClickListener { finish() }
        useCurrentButton.setOnClickListener { useCurrentLocation() }
        clearPointsButton.setOnClickListener {
            polygonPoints.clear()
            redrawPolygon()
        }
        saveButton.setOnClickListener { saveGeoFence() }
        typeSpinner.onItemSelectedListener = object : android.widget.AdapterView.OnItemSelectedListener {
            override fun onItemSelected(parent: android.widget.AdapterView<*>?, view: View?, position: Int, id: Long) {
                updateTypeUi(getSelectedType())
            }
            override fun onNothingSelected(parent: android.widget.AdapterView<*>?) = Unit
        }

        loadOutlet()
    }

    private fun loadOutlet() {
        progressBar.visibility = View.VISIBLE
        saveButton.isEnabled = false
        Thread {
            val result = ApiClient.getOutlet(sessionManager.token ?: "", outletId)
            runOnUiThread {
                progressBar.visibility = View.GONE
                saveButton.isEnabled = true
                when (result) {
                    is ApiClient.ApiResult.Success -> {
                        loadedOutlet = result.data
                        currentLat = result.data.lat
                        currentLng = result.data.lng
                        locationText.text = formatLocation(currentLat, currentLng)
                        centerMap(currentLat, currentLng)
                        val radius = result.data.geoFenceRadiusMetres
                        radiusEdit.setText(radius?.toString() ?: "100")
                        polygonPoints.clear()
                        parsePolygonPoints(result.data.geoFencePolygon).forEach { polygonPoints.add(it) }
                        redrawPolygon()
                        val type = result.data.geoFenceType ?: ""
                        typeSpinner.setSelection(
                            when (type) {
                                "radius" -> 1
                                "polygon" -> 2
                                else -> 0
                            }
                        )
                        updateTypeUi(getSelectedType())
                    }
                    is ApiClient.ApiResult.Error -> {
                        Toast.makeText(this, result.message, Toast.LENGTH_LONG).show()
                    }
                }
            }
        }.start()
    }

    private fun useCurrentLocation() {
        if (!hasLocationPermission()) {
            locationPermissionLauncher.launch(Manifest.permission.ACCESS_FINE_LOCATION)
            return
        }
        fetchLastKnownLocation()
    }

    private fun saveGeoFence() {
        val outlet = loadedOutlet
        if (outlet == null) {
            Toast.makeText(this, R.string.error_loading_outlets, Toast.LENGTH_SHORT).show()
            return
        }
        val selectedType = getSelectedType()
        val lat = currentLat
        val lng = currentLng
        var radiusToSave: Int? = null
        var polygonToSave: String? = null

        if (selectedType == "radius") {
            if (lat == null || lng == null) {
                Toast.makeText(this, R.string.outlet_location_get, Toast.LENGTH_SHORT).show()
                return
            }
            val radius = radiusEdit.text?.toString()?.trim()?.toIntOrNull()
            if (radius == null || radius < 10) {
                radiusEdit.error = getString(R.string.outlet_geofence_radius_error)
                return
            }
            radiusToSave = radius
        } else if (selectedType == "polygon") {
            if (polygonPoints.size < 4) {
                Toast.makeText(this, R.string.outlet_geofence_polygon_error, Toast.LENGTH_SHORT).show()
                return
            }
            polygonToSave = buildPolygonJson(polygonPoints)
        }

        progressBar.visibility = View.VISIBLE
        saveButton.isEnabled = false
        Thread {
            val result = ApiClient.updateOutlet(
                token = sessionManager.token ?: "",
                outletId = outletId,
                name = outlet.name,
                code = outlet.code,
                address = outlet.address,
                lat = lat,
                lng = lng,
                geoFenceType = selectedType.ifBlank { null },
                geoFenceRadiusMetres = radiusToSave,
                geoFencePolygon = polygonToSave
            )
            runOnUiThread {
                progressBar.visibility = View.GONE
                saveButton.isEnabled = true
                when (result) {
                    is ApiClient.ApiResult.Success -> {
                        Toast.makeText(this, R.string.outlet_geofence_saved, Toast.LENGTH_SHORT).show()
                        setResult(RESULT_OK)
                        finish()
                    }
                    is ApiClient.ApiResult.Error -> {
                        Toast.makeText(this, result.message, Toast.LENGTH_LONG).show()
                    }
                }
            }
        }.start()
    }

    private fun formatLocation(lat: Double?, lng: Double?): String {
        return if (lat != null && lng != null) {
            "%.6f, %.6f".format(lat, lng)
        } else {
            "—"
        }
    }

    private fun getSelectedType(): String {
        return when (typeSpinner.selectedItemPosition) {
            1 -> "radius"
            2 -> "polygon"
            else -> ""
        }
    }

    private fun updateTypeUi(type: String) {
        val showRadius = type == "radius"
        val showPolygon = type == "polygon"
        locationSection.visibility = if (showRadius) View.VISIBLE else View.GONE
        radiusSection.visibility = if (showRadius) View.VISIBLE else View.GONE
        polygonSection.visibility = if (showPolygon) View.VISIBLE else View.GONE
        if (showPolygon) redrawPolygon()
    }

    private fun hasLocationPermission(): Boolean {
        return ContextCompat.checkSelfPermission(
            this,
            Manifest.permission.ACCESS_FINE_LOCATION
        ) == PackageManager.PERMISSION_GRANTED
    }

    private fun fetchLastKnownLocation() {
        try {
            val lm = getSystemService(Context.LOCATION_SERVICE) as? LocationManager
            val loc = lm?.getLastKnownLocation(LocationManager.GPS_PROVIDER)
                ?: lm?.getLastKnownLocation(LocationManager.NETWORK_PROVIDER)
            if (loc != null) {
                currentLat = loc.latitude
                currentLng = loc.longitude
                locationText.text = formatLocation(currentLat, currentLng)
                centerMap(currentLat, currentLng)
            } else {
                Toast.makeText(this, R.string.outlet_location_get, Toast.LENGTH_SHORT).show()
            }
        } catch (_: SecurityException) {
            Toast.makeText(this, R.string.error_location_permission, Toast.LENGTH_SHORT).show()
        }
    }

    private fun setupMap() {
        mapView.setTileSource(TileSourceFactory.MAPNIK)
        mapView.setMultiTouchControls(true)
        mapView.controller.setZoom(15.0)

        val eventsOverlay = MapEventsOverlay(object : MapEventsReceiver {
            override fun singleTapConfirmedHelper(p: GeoPoint): Boolean {
                if (getSelectedType() != "polygon") return false
                polygonPoints.add(GeoPoint(p.latitude, p.longitude))
                redrawPolygon()
                return true
            }

            override fun longPressHelper(p: GeoPoint): Boolean = false
        })
        mapView.overlays.add(eventsOverlay)
    }

    private fun centerMap(lat: Double?, lng: Double?) {
        if (lat == null || lng == null) return
        mapView.controller.setCenter(GeoPoint(lat, lng))
    }

    private fun redrawPolygon() {
        mapView.overlays.removeAll { it is Marker || it is Polygon }
        polygonPoints.forEach { p ->
            val marker = Marker(mapView)
            marker.position = p
            marker.setAnchor(Marker.ANCHOR_CENTER, Marker.ANCHOR_BOTTOM)
            mapView.overlays.add(marker)
        }
        if (polygonPoints.size >= 3) {
            val polygon = Polygon()
            polygon.points = polygonPoints.toList()
            polygon.fillPaint.color = android.graphics.Color.argb(60, 0, 111, 120)
            polygon.outlinePaint.color = android.graphics.Color.rgb(0, 111, 120)
            polygon.outlinePaint.strokeWidth = 3f
            mapView.overlays.add(polygon)
        }
        mapView.invalidate()
    }

    private fun parsePolygonPoints(raw: String?): List<GeoPoint> {
        if (raw.isNullOrBlank()) return emptyList()
        return try {
            val arr = JSONArray(raw)
            buildList {
                for (i in 0 until arr.length()) {
                    val obj = arr.optJSONObject(i) ?: continue
                    val lat = obj.optDouble("lat", Double.NaN)
                    val lng = obj.optDouble("lng", Double.NaN)
                    if (!lat.isNaN() && !lng.isNaN()) add(GeoPoint(lat, lng))
                }
            }
        } catch (_: Exception) {
            emptyList()
        }
    }

    private fun buildPolygonJson(points: List<GeoPoint>): String {
        val arr = JSONArray()
        points.forEach { p ->
            arr.put(JSONObject().apply {
                put("lat", p.latitude)
                put("lng", p.longitude)
            })
        }
        return arr.toString()
    }

    override fun onResume() {
        super.onResume()
        mapView.onResume()
    }

    override fun onPause() {
        super.onPause()
        mapView.onPause()
    }
}

