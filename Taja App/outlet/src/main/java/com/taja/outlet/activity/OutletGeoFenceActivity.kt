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
import android.widget.TextView
import android.widget.Toast
import androidx.activity.result.contract.ActivityResultContracts
import androidx.appcompat.app.AppCompatActivity
import androidx.core.content.ContextCompat
import com.taja.outlet.ApiClient
import com.taja.outlet.R
import com.taja.outlet.SessionManager

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
    private lateinit var locationText: TextView
    private lateinit var radiusEdit: EditText
    private lateinit var saveButton: Button
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
        locationText = findViewById(R.id.outlet_geofence_location_value)
        radiusEdit = findViewById(R.id.outlet_geofence_radius)
        saveButton = findViewById(R.id.outlet_geofence_save)

        findViewById<Button>(R.id.outlet_geofence_back).setOnClickListener { finish() }
        findViewById<Button>(R.id.outlet_geofence_use_current).setOnClickListener { useCurrentLocation() }
        saveButton.setOnClickListener { saveGeoFence() }

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
                        val radius = result.data.geoFenceRadiusMetres
                        radiusEdit.setText(radius?.toString() ?: "100")
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
        val lat = currentLat
        val lng = currentLng
        if (lat == null || lng == null) {
            Toast.makeText(this, R.string.outlet_location_get, Toast.LENGTH_SHORT).show()
            return
        }
        val radius = radiusEdit.text?.toString()?.trim()?.toIntOrNull()
        if (radius == null || radius < 10) {
            radiusEdit.error = getString(R.string.outlet_geofence_radius_error)
            return
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
                geoFenceType = "radius",
                geoFenceRadiusMetres = radius
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
            } else {
                Toast.makeText(this, R.string.outlet_location_get, Toast.LENGTH_SHORT).show()
            }
        } catch (_: SecurityException) {
            Toast.makeText(this, R.string.error_location_permission, Toast.LENGTH_SHORT).show()
        }
    }
}

