package com.taja.app.activity

import android.content.Intent
import android.os.Bundle
import android.view.View
import android.widget.Button
import android.widget.ProgressBar
import android.widget.TextView
import androidx.appcompat.app.AppCompatActivity
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView
import com.google.android.material.navigation.NavigationBarView
import com.taja.app.ApiClient
import com.taja.app.CheckInQueue
import com.taja.app.R
import com.taja.app.SessionManager
import com.taja.app.adapter.OutletsAdapter

class OutletsListActivity : AppCompatActivity() {

    private lateinit var sessionManager: SessionManager
    private lateinit var recycler: RecyclerView
    private lateinit var progressBar: ProgressBar
    private lateinit var emptyText: TextView
    private lateinit var bottomNavigation: NavigationBarView
    private lateinit var adapter: OutletsAdapter
    private lateinit var checkInQueue: CheckInQueue
    private var permissionCheckInCallback: ((Boolean) -> Unit)? = null

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_outlets_list)
        sessionManager = SessionManager(this)
        if (!sessionManager.isLoggedIn) {
            startActivity(Intent(this, LoginActivity::class.java))
            finish()
            return
        }
        recycler = findViewById(R.id.outlets_recycler)
        progressBar = findViewById(R.id.outlets_progress)
        emptyText = findViewById(R.id.outlets_empty_text)
        findViewById<Button>(R.id.outlets_list_back).setOnClickListener { finish() }
        bottomNavigation = findViewById(R.id.bottom_navigation)
        recycler.layoutManager = LinearLayoutManager(this)
        adapter = OutletsAdapter(emptyList()) { outlet -> openCheckInSheet(outlet) }
        recycler.adapter = adapter
        checkInQueue = CheckInQueue(this)
        bottomNavigation.selectedItemId = R.id.nav_outlets
        bottomNavigation.setOnItemSelectedListener { item ->
            when (item.itemId) {
                R.id.nav_dashboard -> {
                    startActivity(Intent(this, DashboardActivity::class.java))
                    finish()
                    false
                }
                R.id.nav_outlets -> true
                R.id.nav_profile -> {
                    startActivity(Intent(this, ProfileActivity::class.java).apply {
                        sessionManager.userName?.let { putExtra(ProfileActivity.EXTRA_NAME, it) }
                        sessionManager.branchName?.let { putExtra(ProfileActivity.EXTRA_BRANCH, it) }
                    })
                    finish()
                    false
                }
                else -> false
            }
        }
        loadOutlets()
    }

    override fun onResume() {
        super.onResume()
        if (sessionManager.isLoggedIn) {
            loadOutlets()
            trySyncPendingCheckIns()
        }
    }

    private fun loadOutlets() {
        val token = sessionManager.token
        if (token.isNullOrBlank()) return
        progressBar.visibility = View.VISIBLE
        emptyText.visibility = View.GONE
        recycler.visibility = View.GONE
        Thread {
            val result = ApiClient.getOutlets(token)
            runOnUiThread {
                progressBar.visibility = View.GONE
                when (result) {
                    is ApiClient.ApiResult.Success -> {
                        val list = result.data
                        adapter.setOutlets(list)
                        if (list.isEmpty()) {
                            emptyText.visibility = View.VISIBLE
                            recycler.visibility = View.GONE
                        } else {
                            emptyText.visibility = View.GONE
                            recycler.visibility = View.VISIBLE
                        }
                    }
                    is ApiClient.ApiResult.Error -> {
                        emptyText.visibility = View.VISIBLE
                        recycler.visibility = View.GONE
                    }
                }
            }
        }.start()
    }

    private fun openCheckInSheet(outlet: ApiClient.Outlet) {
        val token = sessionManager.token ?: return
        val sheet = com.google.android.material.bottomsheet.BottomSheetDialog(this, R.style.AppBottomSheetDialogTheme)
        val view = layoutInflater.inflate(R.layout.bottomsheet_checkin, null)
        sheet.setContentView(view)
        view.findViewById<TextView>(R.id.bottomsheet_checkin_outlet_name).text = outlet.name
        val locationValue = view.findViewById<TextView>(R.id.bottomsheet_checkin_location_value)
        val notesEdit = view.findViewById<android.widget.EditText>(R.id.bottomsheet_checkin_notes)
        locationValue.text = getString(R.string.check_in_location_getting)
        var lastLat: Double? = null
        var lastLng: Double? = null
        fetchCurrentLocation(
            onSuccess = { lat, lng ->
                runOnUiThread {
                    lastLat = lat
                    lastLng = lng
                    locationValue.text = "%.5f, %.5f".format(lat, lng)
                }
            },
            onUnavailable = {
                runOnUiThread {
                    locationValue.text = getString(R.string.check_in_location_required)
                }
            }
        )
        view.findViewById<android.widget.Button>(R.id.bottomsheet_checkin_cancel).setOnClickListener { sheet.dismiss() }
        view.findViewById<android.widget.Button>(R.id.bottomsheet_checkin_submit).setOnClickListener {
            val lat = lastLat
            val lng = lastLng
            if (lat == null || lng == null) {
                android.widget.Toast.makeText(this, getString(R.string.check_in_location_required), android.widget.Toast.LENGTH_SHORT).show()
                return@setOnClickListener
            }
            val notes = notesEdit.text?.toString()?.takeIf { it.isNotBlank() }
            sheet.dismiss()
            submitCheckIn(token, outlet.id, lat, lng, notes)
        }
        sheet.window?.setBackgroundDrawableResource(android.R.color.transparent)
        sheet.show()
    }

    private fun fetchCurrentLocation(onSuccess: (Double, Double) -> Unit, onUnavailable: () -> Unit = {}) {
        if (!hasLocationPermission()) {
            requestLocationPermissionForCheckIn { granted ->
                if (granted) doFetchLocation(onSuccess, onUnavailable) else runOnUiThread { onUnavailable() }
            }
            return
        }
        doFetchLocation(onSuccess, onUnavailable)
    }

    private fun hasLocationPermission(): Boolean {
        return androidx.core.content.ContextCompat.checkSelfPermission(this, android.Manifest.permission.ACCESS_FINE_LOCATION) == android.content.pm.PackageManager.PERMISSION_GRANTED
    }

    private fun requestLocationPermissionForCheckIn(onResult: (Boolean) -> Unit) {
        androidx.core.app.ActivityCompat.requestPermissions(this, arrayOf(android.Manifest.permission.ACCESS_FINE_LOCATION), REQUEST_LOCATION_CHECKIN)
        permissionCheckInCallback = onResult
    }

    private fun doFetchLocation(onSuccess: (Double, Double) -> Unit, onUnavailable: () -> Unit = {}) {
        val lm = getSystemService(android.content.Context.LOCATION_SERVICE) as android.location.LocationManager
        val loc = lm.getLastKnownLocation(android.location.LocationManager.GPS_PROVIDER)
            ?: lm.getLastKnownLocation(android.location.LocationManager.NETWORK_PROVIDER)
        if (loc != null) {
            onSuccess(loc.latitude, loc.longitude)
            return
        }
        if (!hasLocationPermission()) {
            runOnUiThread { onUnavailable() }
            return
        }
        lm.requestSingleUpdate(android.location.LocationManager.GPS_PROVIDER, { location ->
            onSuccess(location.latitude, location.longitude)
        }, null)
        // If no update arrives, onUnavailable is never called; user can still try submit (will show "location required" if null)
    }

    private fun submitCheckIn(token: String, outletId: String, lat: Double, lng: Double, notes: String?) {
        progressBar.visibility = View.VISIBLE
        Thread {
            val online = CheckInQueue.isNetworkAvailable(this)
            if (online) {
                val result = ApiClient.createCheckIn(token, outletId, lat, lng, notes, null)
                runOnUiThread {
                    progressBar.visibility = View.GONE
                    when (result) {
                        is ApiClient.ApiResult.Success ->
                            android.widget.Toast.makeText(this, getString(R.string.check_in_success), android.widget.Toast.LENGTH_SHORT).show()
                        is ApiClient.ApiResult.Error -> {
                            // Network or server error: queue for later sync
                            checkInQueue.add(outletId, lat, lng, notes, null)
                            android.widget.Toast.makeText(this, getString(R.string.check_in_saved_offline), android.widget.Toast.LENGTH_SHORT).show()
                        }
                    }
                }
            } else {
                checkInQueue.add(outletId, lat, lng, notes, null)
                runOnUiThread {
                    progressBar.visibility = View.GONE
                    android.widget.Toast.makeText(this, getString(R.string.check_in_saved_offline), android.widget.Toast.LENGTH_SHORT).show()
                }
            }
        }.start()
    }

    private fun trySyncPendingCheckIns() {
        val token = sessionManager.token ?: return
        val pending = checkInQueue.getAll()
        if (pending.isEmpty() || !CheckInQueue.isNetworkAvailable(this)) return
        Thread {
            val result = ApiClient.syncCheckIns(token, pending)
            runOnUiThread {
                when (result) {
                    is ApiClient.ApiResult.Success -> {
                        val syncedIds = result.data.synced.map { it.clientId }.toSet()
                        checkInQueue.removeByClientIds(syncedIds)
                        if (syncedIds.isNotEmpty()) {
                            android.widget.Toast.makeText(
                                this,
                                getString(R.string.check_in_synced, syncedIds.size),
                                android.widget.Toast.LENGTH_SHORT
                            ).show()
                        }
                    }
                    is ApiClient.ApiResult.Error -> { /* keep queue for next resume */ }
                }
            }
        }.start()
    }

    override fun onRequestPermissionsResult(requestCode: Int, permissions: Array<out String>, grantResults: IntArray) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults)
        if (requestCode == REQUEST_LOCATION_CHECKIN) {
            permissionCheckInCallback?.invoke(grantResults.isNotEmpty() && grantResults[0] == android.content.pm.PackageManager.PERMISSION_GRANTED)
            permissionCheckInCallback = null
        }
    }

    companion object {
        private const val REQUEST_LOCATION_CHECKIN = 9001
    }
}
