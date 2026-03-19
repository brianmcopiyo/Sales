package com.taja.outlet.activity

import android.content.Intent
import android.os.Bundle
import android.view.View
import android.widget.Button
import android.widget.ProgressBar
import android.widget.TextView
import android.widget.Toast
import androidx.activity.result.contract.ActivityResultContracts
import androidx.appcompat.app.AppCompatActivity
import com.taja.outlet.ApiClient
import com.taja.outlet.R
import com.taja.outlet.SessionManager

class OutletDetailActivity : AppCompatActivity() {

    companion object {
        const val EXTRA_OUTLET_ID = "outlet_id"
    }

    private lateinit var sessionManager: SessionManager
    private lateinit var outletId: String
    private lateinit var progressBar: ProgressBar
    private lateinit var nameText: TextView
    private lateinit var codeText: TextView
    private lateinit var addressText: TextView
    private lateinit var locationText: TextView

    private val editLauncher = registerForActivityResult(ActivityResultContracts.StartActivityForResult()) { result ->
        if (result.resultCode == RESULT_OK) {
            setResult(RESULT_OK)
            loadOutlet()
        }
    }
    private val geofenceLauncher = registerForActivityResult(ActivityResultContracts.StartActivityForResult()) { result ->
        if (result.resultCode == RESULT_OK) {
            setResult(RESULT_OK)
            loadOutlet()
        }
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_outlet_detail)
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

        findViewById<Button>(R.id.outlet_detail_back).setOnClickListener { finish() }
        findViewById<Button>(R.id.outlet_detail_edit).setOnClickListener {
            editLauncher.launch(
                Intent(this, OutletFormActivity::class.java)
                    .putExtra(OutletFormActivity.EXTRA_OUTLET_ID, outletId)
            )
        }
        findViewById<Button>(R.id.outlet_detail_geofence).setOnClickListener {
            geofenceLauncher.launch(
                Intent(this, OutletGeoFenceActivity::class.java)
                    .putExtra(OutletGeoFenceActivity.EXTRA_OUTLET_ID, outletId)
            )
        }

        progressBar = findViewById(R.id.outlet_detail_progress)
        nameText = findViewById(R.id.outlet_detail_name_value)
        codeText = findViewById(R.id.outlet_detail_code_value)
        addressText = findViewById(R.id.outlet_detail_address_value)
        locationText = findViewById(R.id.outlet_detail_location_value)

        loadOutlet()
    }

    private fun loadOutlet() {
        progressBar.visibility = View.VISIBLE
        Thread {
            val result = ApiClient.getOutlet(sessionManager.token ?: "", outletId)
            runOnUiThread {
                progressBar.visibility = View.GONE
                when (result) {
                    is ApiClient.ApiResult.Success -> bindOutlet(result.data)
                    is ApiClient.ApiResult.Error ->
                        Toast.makeText(this, result.message, Toast.LENGTH_LONG).show()
                }
            }
        }.start()
    }

    private fun bindOutlet(outlet: ApiClient.Outlet) {
        nameText.text = outlet.name.ifBlank { "—" }
        codeText.text = outlet.code?.takeIf { it.isNotBlank() } ?: "—"
        addressText.text = outlet.address?.takeIf { it.isNotBlank() } ?: "—"
        locationText.text = if (outlet.lat != null && outlet.lng != null) {
            "%.6f, %.6f".format(outlet.lat, outlet.lng)
        } else {
            "—"
        }
    }
}

