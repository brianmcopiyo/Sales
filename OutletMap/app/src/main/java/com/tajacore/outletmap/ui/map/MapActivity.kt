package com.tajacore.outletmap.ui.map

import android.Manifest
import android.content.pm.PackageManager
import android.os.Bundle
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.core.content.ContextCompat
import androidx.core.view.isVisible
import androidx.lifecycle.lifecycleScope
import com.google.android.gms.maps.CameraUpdateFactory
import com.google.android.gms.maps.GoogleMap
import com.google.android.gms.maps.OnMapReadyCallback
import com.google.android.gms.maps.SupportMapFragment
import com.google.android.gms.maps.model.CircleOptions
import com.google.android.gms.maps.model.LatLng
import com.google.android.gms.maps.model.Marker
import com.google.android.gms.maps.model.MarkerOptions
import com.google.android.material.dialog.MaterialAlertDialogBuilder
import com.tajacore.outletmap.R
import com.tajacore.outletmap.data.AuthRepository
import com.tajacore.outletmap.data.api.CreateOutletRequest
import com.tajacore.outletmap.data.api.RetrofitModule
import com.tajacore.outletmap.data.api.UpdateOutletRequest
import com.tajacore.outletmap.data.api.dto.OutletDto
import com.tajacore.outletmap.databinding.ActivityMapBinding
import com.tajacore.outletmap.ui.login.LoginActivity
import kotlinx.coroutines.launch
import android.content.Intent

class MapActivity : AppCompatActivity(), OnMapReadyCallback {

    private lateinit var binding: ActivityMapBinding
    private lateinit var authRepo: AuthRepository
    private var map: GoogleMap? = null
    private var outlets: List<OutletDto> = emptyList()
    private val markers = mutableMapOf<String, Marker>()
    private var pendingLatLng: LatLng? = null
    private var selectedOutlet: OutletDto? = null

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityMapBinding.inflate(layoutInflater)
        setContentView(binding.root)
        authRepo = AuthRepository(this)

        lifecycleScope.launch {
            if (authRepo.getToken() == null) {
                startActivity(Intent(this@MapActivity, LoginActivity::class.java).apply {
                    flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK
                })
                finish()
                return@launch
            }
        }

        val mapFragment = supportFragmentManager.findFragmentById(R.id.map) as? SupportMapFragment
        mapFragment?.getMapAsync(this)

        binding.addOutletFab.setOnClickListener {
            pendingLatLng = null
            selectedOutlet = null
            binding.tapHint.isVisible = true
            Toast.makeText(this, R.string.tap_map_to_place, Toast.LENGTH_SHORT).show()
        }

        binding.logout.setOnClickListener {
            lifecycleScope.launch {
                authRepo.clearToken()
                startActivity(Intent(this@MapActivity, LoginActivity::class.java).apply {
                    flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK
                })
                finish()
            }
        }
    }

    override fun onMapReady(googleMap: GoogleMap) {
        map = googleMap
        if (ContextCompat.checkSelfPermission(this, Manifest.permission.ACCESS_FINE_LOCATION) == PackageManager.PERMISSION_GRANTED) {
            googleMap.isMyLocationEnabled = true
        } else {
            requestPermissions(arrayOf(Manifest.permission.ACCESS_FINE_LOCATION), 1)
        }
        googleMap.uiSettings.isZoomControlsEnabled = true
        googleMap.setOnMapClickListener { latLng ->
            if (binding.tapHint.isVisible) {
                pendingLatLng = latLng
                binding.tapHint.isVisible = false
                showOutletDialog(latLng, null)
            }
        }
        loadOutlets()
    }

    private fun loadOutlets() {
        lifecycleScope.launch {
            val token = authRepo.getToken() ?: return@launch
            val baseUrl = authRepo.getBaseUrl() ?: "http://10.0.2.2:8000/api/"
            try {
                val api = RetrofitModule.apiService(baseUrl)
                val response = api.getOutlets("Bearer $token")
                if (response.isSuccessful) {
                    outlets = response.body()?.outlets ?: emptyList()
                    updateMarkers()
                    if (outlets.isNotEmpty() && map != null) {
                        val first = outlets.first()
                        val lat = first.lat ?: -6.0
                        val lng = first.lng ?: 35.0
                        map?.moveCamera(CameraUpdateFactory.newLatLngZoom(LatLng(lat, lng), 12f))
                    }
                } else if (response.code() == 401) {
                    authRepo.clearToken()
                    startActivity(Intent(this@MapActivity, LoginActivity::class.java))
                    finish()
                }
            } catch (e: Exception) {
                Toast.makeText(this@MapActivity, e.message ?: "Error", Toast.LENGTH_SHORT).show()
            }
        }
    }

    private fun updateMarkers() {
        val m = map ?: return
        markers.values.forEach { it.remove() }
        markers.clear()
        outlets.forEach { outlet ->
            val lat = outlet.lat ?: return@forEach
            val lng = outlet.lng ?: return@forEach
            val pos = LatLng(lat, lng)
            val marker = m.addMarker(
                MarkerOptions().position(pos).title(outlet.name)
            ) ?: return@forEach
            marker.tag = outlet
            markers[outlet.id] = marker
            if (outlet.geoFenceType == "radius" && outlet.geoFenceRadiusMetres != null) {
                m.addCircle(
                    CircleOptions().center(pos).radius(outlet.geoFenceRadiusMetres.toDouble())
                        .strokeWidth(2f).fillColor(0x22006F78)
                )
            }
        }
        m.setOnMarkerClickListener { marker ->
            val outlet = marker.tag as? OutletDto ?: return@setOnMarkerClickListener false
            selectedOutlet = outlet
            pendingLatLng = LatLng(outlet.lat!!, outlet.lng!!)
            showOutletDialog(pendingLatLng!!, outlet)
            true
        }
    }

    private fun showOutletDialog(latLng: LatLng, existing: OutletDto?) {
        val view = layoutInflater.inflate(R.layout.dialog_outlet_form, null)
        val nameField = view.findViewById<com.google.android.material.textfield.TextInputEditText>(R.id.outletName)
        val addressField = view.findViewById<com.google.android.material.textfield.TextInputEditText>(R.id.outletAddress)
        val radiusField = view.findViewById<com.google.android.material.textfield.TextInputEditText>(R.id.radiusMetres)
        if (existing != null) {
            nameField.setText(existing.name)
            addressField.setText(existing.address ?: "")
            radiusField.setText(existing.geoFenceRadiusMetres?.toString() ?: "100")
        } else {
            radiusField.setText("100")
        }
        val dialog = MaterialAlertDialogBuilder(this)
            .setTitle(if (existing != null) "Update outlet location" else "New outlet")
            .setView(view)
            .setPositiveButton(R.string.save) { _, _ ->
                val name = nameField.text?.toString()?.trim() ?: ""
                if (name.isBlank()) {
                    Toast.makeText(this, "Name required", Toast.LENGTH_SHORT).show()
                    return@setPositiveButton
                }
                val address = addressField.text?.toString()?.trim()
                val radius = radiusField.text?.toString()?.toIntOrNull()?.coerceIn(10, 5000)
                if (existing != null) {
                    updateOutlet(existing.id, name, address, radius, latLng)
                } else {
                    createOutlet(name, address, radius, latLng)
                }
            }
            .setNegativeButton(R.string.cancel, null)
            .show()
    }

    private fun createOutlet(name: String, address: String?, radius: Int?, latLng: LatLng) {
        lifecycleScope.launch {
            val token = authRepo.getToken() ?: return@launch
            val baseUrl = authRepo.getBaseUrl() ?: "http://10.0.2.2:8000/api/"
            try {
                val api = RetrofitModule.apiService(baseUrl)
                val body = CreateOutletRequest(
                    name = name,
                    lat = latLng.latitude,
                    lng = latLng.longitude,
                    address = address,
                    geo_fence_type = if (radius != null) "radius" else null,
                    geo_fence_radius_metres = radius
                )
                val response = api.createOutlet("Bearer $token", body)
                if (response.isSuccessful) {
                    Toast.makeText(this@MapActivity, "Outlet created", Toast.LENGTH_SHORT).show()
                    loadOutlets()
                } else {
                    Toast.makeText(this@MapActivity, response.message() ?: "Failed", Toast.LENGTH_SHORT).show()
                }
            } catch (e: Exception) {
                Toast.makeText(this@MapActivity, e.message ?: "Error", Toast.LENGTH_SHORT).show()
            }
        }
    }

    private fun updateOutlet(id: String, name: String, address: String?, radius: Int?, latLng: LatLng) {
        lifecycleScope.launch {
            val token = authRepo.getToken() ?: return@launch
            val baseUrl = authRepo.getBaseUrl() ?: "http://10.0.2.2:8000/api/"
            try {
                val api = RetrofitModule.apiService(baseUrl)
                val body = UpdateOutletRequest(
                    name = name,
                    lat = latLng.latitude,
                    lng = latLng.longitude,
                    address = address,
                    geo_fence_type = if (radius != null) "radius" else null,
                    geo_fence_radius_metres = radius
                )
                val response = api.updateOutlet("Bearer $token", id, body)
                if (response.isSuccessful) {
                    Toast.makeText(this@MapActivity, "Outlet updated", Toast.LENGTH_SHORT).show()
                    loadOutlets()
                } else {
                    Toast.makeText(this@MapActivity, response.message() ?: "Failed", Toast.LENGTH_SHORT).show()
                }
            } catch (e: Exception) {
                Toast.makeText(this@MapActivity, e.message ?: "Error", Toast.LENGTH_SHORT).show()
            }
        }
    }

    override fun onRequestPermissionsResult(requestCode: Int, permissions: Array<out String>, grantResults: IntArray) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults)
        if (requestCode == 1 && grantResults.isNotEmpty() && grantResults[0] == PackageManager.PERMISSION_GRANTED) {
            map?.isMyLocationEnabled = true
        }
    }
}
