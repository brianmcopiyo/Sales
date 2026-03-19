package com.taja.outlet.activity

import android.content.Intent
import android.os.Bundle
import android.widget.Button
import android.widget.ProgressBar
import android.widget.TextView
import androidx.appcompat.app.AppCompatActivity
import com.google.android.material.bottomnavigation.BottomNavigationView
import com.taja.outlet.ApiClient
import com.taja.outlet.R
import com.taja.outlet.SessionManager
import org.osmdroid.config.Configuration
import org.osmdroid.util.GeoPoint
import org.osmdroid.views.MapView
import org.osmdroid.views.overlay.Marker

class OutletMapActivity : AppCompatActivity() {
    private lateinit var sessionManager: SessionManager
    private lateinit var mapView: MapView
    private lateinit var progressBar: ProgressBar
    private lateinit var emptyText: TextView
    private lateinit var bottomNav: BottomNavigationView

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        sessionManager = SessionManager(this)
        if (!sessionManager.isLoggedIn) {
            startActivity(Intent(this, LoginActivity::class.java))
            finish()
            return
        }
        Configuration.getInstance().load(applicationContext, getSharedPreferences("osmdroid", MODE_PRIVATE))
        setContentView(R.layout.activity_outlet_map)
        mapView = findViewById(R.id.outlet_map_view)
        progressBar = findViewById(R.id.outlet_map_progress)
        emptyText = findViewById(R.id.outlet_map_empty)
        bottomNav = findViewById(R.id.bottom_navigation)

        mapView.setMultiTouchControls(true)
        mapView.controller.setZoom(13.0)
        mapView.controller.setCenter(GeoPoint(-1.286389, 36.817223))

        findViewById<Button>(R.id.outlet_map_back).setOnClickListener { finish() }
        findViewById<Button>(R.id.outlet_map_action_list).setOnClickListener {
            startActivity(Intent(this, OutletListActivity::class.java))
        }
        bottomNav.selectedItemId = R.id.nav_map
        bottomNav.setOnItemSelectedListener { item ->
            when (item.itemId) {
                R.id.nav_map -> true
                R.id.nav_outlets -> {
                    startActivity(Intent(this, OutletListActivity::class.java))
                    finish()
                    false
                }
                R.id.nav_profile -> false
                else -> false
            }
        }
        loadMapOutlets()
    }

    private fun loadMapOutlets() {
        val token = sessionManager.token ?: return
        progressBar.visibility = android.view.View.VISIBLE
        emptyText.visibility = android.view.View.GONE
        Thread {
            val result = ApiClient.getOutlets(token)
            runOnUiThread {
                progressBar.visibility = android.view.View.GONE
                when (result) {
                    is ApiClient.ApiResult.Success -> {
                        mapView.overlays.removeAll { it is Marker }
                        val mapped = result.data.filter { it.lat != null && it.lng != null }
                        if (mapped.isEmpty()) {
                            emptyText.visibility = android.view.View.VISIBLE
                            mapView.invalidate()
                            return@runOnUiThread
                        }
                        emptyText.visibility = android.view.View.GONE
                        mapped.forEach { outlet ->
                            val marker = Marker(mapView).apply {
                                position = GeoPoint(outlet.lat!!, outlet.lng!!)
                                title = outlet.name
                                subDescription = outlet.address ?: outlet.code.orEmpty()
                                setAnchor(Marker.ANCHOR_CENTER, Marker.ANCHOR_BOTTOM)
                                setOnMarkerClickListener { _, _ ->
                                    startActivity(
                                        Intent(this@OutletMapActivity, OutletDetailActivity::class.java)
                                            .putExtra(OutletDetailActivity.EXTRA_OUTLET_ID, outlet.id)
                                    )
                                    true
                                }
                            }
                            mapView.overlays.add(marker)
                        }
                        val first = mapped.first()
                        mapView.controller.animateTo(GeoPoint(first.lat!!, first.lng!!))
                        mapView.invalidate()
                    }
                    is ApiClient.ApiResult.Error -> {
                        emptyText.visibility = android.view.View.VISIBLE
                    }
                }
            }
        }.start()
    }

    override fun onResume() {
        super.onResume()
        mapView.onResume()
    }

    override fun onPause() {
        mapView.onPause()
        super.onPause()
    }
}
