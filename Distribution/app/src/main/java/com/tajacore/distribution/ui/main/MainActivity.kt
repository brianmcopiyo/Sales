package com.tajacore.distribution.ui.main

import android.content.Intent
import android.os.Bundle
import android.widget.Toast
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.getValue
import androidx.compose.runtime.setValue
import androidx.lifecycle.lifecycleScope
import com.tajacore.distribution.data.AuthRepository
import com.tajacore.distribution.data.api.RetrofitModule
import com.tajacore.distribution.data.api.dto.OutletDto
import com.tajacore.distribution.data.local.createAppDatabase
import com.tajacore.distribution.geofence.GeofenceHelper
import com.tajacore.distribution.ui.checkin.CheckInActivity
import com.tajacore.distribution.ui.navigation.DistributionNavGraph
import com.tajacore.distribution.ui.theme.TajaCoreDistributionTheme
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext

class MainActivity : ComponentActivity() {

    private lateinit var authRepo: AuthRepository

    private var outlets by mutableStateOf<List<OutletDto>>(emptyList())
    private var pendingCount by mutableStateOf(0)

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        authRepo = AuthRepository(this)

        setContent {
            TajaCoreDistributionTheme {
                DistributionNavGraph(
                    authRepo = authRepo,
                    outlets = outlets,
                    pendingCount = pendingCount,
                    loadOutletsAndPending = { onUnauthorized ->
                        lifecycleScope.launch {
                            val token = authRepo.getToken()
                            if (token == null) {
                                withContext(Dispatchers.Main) { onUnauthorized() }
                                return@launch
                            }
                            val baseUrl = authRepo.getBaseUrl()
                            try {
                                val api = RetrofitModule.apiService(baseUrl)
                                val response = api.getOutlets("Bearer $token")
                                if (response.isSuccessful) {
                                    val list = response.body()!!.outlets
                                    withContext(Dispatchers.Main) { outlets = list }
                                    GeofenceHelper(this@MainActivity).registerOutlets(list)
                                } else {
                                    if (response.code() == 401) {
                                        authRepo.clearToken()
                                        withContext(Dispatchers.Main) { onUnauthorized() }
                                        return@launch
                                    }
                                    withContext(Dispatchers.Main) {
                                        Toast.makeText(this@MainActivity, "Failed to load outlets", Toast.LENGTH_SHORT).show()
                                    }
                                }
                            } catch (e: Exception) {
                                withContext(Dispatchers.Main) {
                                    Toast.makeText(this@MainActivity, e.message ?: "Error", Toast.LENGTH_SHORT).show()
                                }
                            }
                            val db = createAppDatabase(this@MainActivity)
                            val pending = db.pendingCheckInDao().getAll()
                            withContext(Dispatchers.Main) { pendingCount = pending.size }
                        }
                    },
                    onOpenCheckIn = { outletId, outletName ->
                        startActivity(Intent(this@MainActivity, CheckInActivity::class.java).apply {
                            putExtra(CheckInActivity.EXTRA_OUTLET_ID, outletId)
                            putExtra(CheckInActivity.EXTRA_OUTLET_NAME, outletName)
                        })
                    }
                )
            }
        }
    }

    override fun onResume() {
        super.onResume()
        lifecycleScope.launch {
            if (authRepo.getToken() != null) {
                loadOutletsAndPendingSilent()
            }
        }
    }

    private fun loadOutletsAndPendingSilent() {
        lifecycleScope.launch {
            val token = authRepo.getToken() ?: return@launch
            val baseUrl = authRepo.getBaseUrl()
            try {
                val api = RetrofitModule.apiService(baseUrl)
                val response = api.getOutlets("Bearer $token")
                if (response.isSuccessful) {
                    val list = response.body()!!.outlets
                    withContext(Dispatchers.Main) { outlets = list }
                    GeofenceHelper(this@MainActivity).registerOutlets(list)
                }
            } catch (_: Exception) { }
            val db = createAppDatabase(this@MainActivity)
            val pending = db.pendingCheckInDao().getAll()
            withContext(Dispatchers.Main) { pendingCount = pending.size }
        }
    }
}
