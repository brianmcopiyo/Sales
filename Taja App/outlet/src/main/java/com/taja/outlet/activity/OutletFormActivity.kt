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

class OutletFormActivity : AppCompatActivity() {

    companion object {
        const val EXTRA_OUTLET_ID = "outlet_id"
    }

    private lateinit var sessionManager: SessionManager
    private var outletId: String? = null
    private var currentLat: Double = 0.0
    private var currentLng: Double = 0.0
    private lateinit var nameEdit: EditText
    private lateinit var codeEdit: EditText
    private lateinit var addressEdit: EditText
    private lateinit var locationDisplay: TextView
    private lateinit var saveButton: Button
    private var formProgress: ProgressBar? = null
    private val locationPermissionLauncher = registerForActivityResult(
        ActivityResultContracts.RequestPermission()
    ) { granted ->
        if (granted) {
            tryGetLastLocation()
        } else {
            Toast.makeText(this, R.string.error_location_permission, Toast.LENGTH_SHORT).show()
        }
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_outlet_form)
        sessionManager = SessionManager(this)
        if (!sessionManager.isLoggedIn) {
            Toast.makeText(this, R.string.error_session, Toast.LENGTH_SHORT).show()
            finish()
            return
        }
        outletId = intent.getStringExtra(EXTRA_OUTLET_ID)
        findViewById<Button>(R.id.outlet_form_back).setOnClickListener { finish() }
        nameEdit = findViewById(R.id.outlet_form_name)
        codeEdit = findViewById(R.id.outlet_form_code)
        addressEdit = findViewById(R.id.outlet_form_address)
        locationDisplay = findViewById(R.id.outlet_form_location_display)
        saveButton = findViewById(R.id.outlet_form_save)
        formProgress = findViewById(R.id.outlet_form_progress)
        tryGetLastLocation()
        if (outletId != null) {
            loadOutlet()
        } else {
            updateLocationDisplay()
        }
        saveButton.setOnClickListener { save() }
    }

    private fun tryGetLastLocation() {
        if (!hasLocationPermission()) {
            locationPermissionLauncher.launch(Manifest.permission.ACCESS_FINE_LOCATION)
            return
        }
        try {
            val lm = getSystemService(Context.LOCATION_SERVICE) as? LocationManager
            val provider = lm?.getLastKnownLocation(LocationManager.GPS_PROVIDER)
                ?: lm?.getLastKnownLocation(LocationManager.NETWORK_PROVIDER)
            provider?.let {
                currentLat = it.latitude
                currentLng = it.longitude
                updateLocationDisplay()
            }
        } catch (_: Exception) { }
    }

    private fun hasLocationPermission(): Boolean {
        return ContextCompat.checkSelfPermission(
            this,
            Manifest.permission.ACCESS_FINE_LOCATION
        ) == PackageManager.PERMISSION_GRANTED
    }

    private fun updateLocationDisplay() {
        locationDisplay.text = if (currentLat != 0.0 || currentLng != 0.0) {
            "%.6f, %.6f".format(currentLat, currentLng)
        } else {
            getString(R.string.outlet_location_get)
        }
    }

    private fun loadOutlet() {
        val id = outletId ?: return
        formProgress?.visibility = View.VISIBLE
        saveButton.isEnabled = false
        Thread {
            val result = ApiClient.getOutlet(sessionManager.token ?: "", id)
            runOnUiThread {
                formProgress?.visibility = View.GONE
                saveButton.isEnabled = true
                when (result) {
                    is ApiClient.ApiResult.Success -> {
                        nameEdit.setText(result.data.name)
                        codeEdit.setText(result.data.code ?: "")
                        addressEdit.setText(result.data.address ?: "")
                        result.data.lat?.let { currentLat = it }
                        result.data.lng?.let { currentLng = it }
                        updateLocationDisplay()
                    }
                    is ApiClient.ApiResult.Error ->
                        Toast.makeText(this, result.message, Toast.LENGTH_LONG).show()
                }
            }
        }.start()
    }

    private fun save() {
        val name = nameEdit.text?.toString()?.trim()
        if (name.isNullOrEmpty()) {
            Toast.makeText(this, R.string.outlet_name_hint, Toast.LENGTH_SHORT).show()
            return
        }
        val code = codeEdit.text?.toString()?.trim()
        val address = addressEdit.text?.toString()?.trim()
        val token = sessionManager.token ?: ""
        formProgress?.visibility = View.VISIBLE
        saveButton.isEnabled = false
        Thread {
            val result = if (outletId != null) {
                ApiClient.updateOutlet(token, outletId!!, name, code, address, currentLat, currentLng)
            } else {
                ApiClient.createOutlet(token, name, code, address, currentLat, currentLng)
            }
            runOnUiThread {
                formProgress?.visibility = View.GONE
                saveButton.isEnabled = true
                when (result) {
                    is ApiClient.ApiResult.Success -> {
                        Toast.makeText(this, R.string.outlet_saved, Toast.LENGTH_SHORT).show()
                        setResult(RESULT_OK)
                        finish()
                    }
                    is ApiClient.ApiResult.Error ->
                        Toast.makeText(this, getString(R.string.error_save_outlet) + ": " + result.message, Toast.LENGTH_LONG).show()
                }
            }
        }.start()
    }
}
