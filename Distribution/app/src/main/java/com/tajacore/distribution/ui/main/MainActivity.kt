package com.tajacore.distribution.ui.main

import android.content.Intent
import android.os.Bundle
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import com.tajacore.distribution.data.AuthRepository
import com.tajacore.distribution.data.api.RetrofitModule
import com.tajacore.distribution.data.local.createAppDatabase
import com.tajacore.distribution.databinding.ActivityMainBinding
import com.tajacore.distribution.geofence.GeofenceHelper
import com.tajacore.distribution.ui.checkin.CheckInActivity
import com.tajacore.distribution.ui.login.LoginActivity
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.launch

class MainActivity : AppCompatActivity() {

    private lateinit var binding: ActivityMainBinding
    private lateinit var authRepo: AuthRepository
    private var adapter: OutletAdapter? = null

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityMainBinding.inflate(layoutInflater)
        setContentView(binding.root)
        authRepo = AuthRepository(this)

        binding.outletList.layoutManager = LinearLayoutManager(this)
        adapter = OutletAdapter(emptyList()) { outlet ->
            startActivity(Intent(this, CheckInActivity::class.java).apply {
                putExtra(CheckInActivity.EXTRA_OUTLET_ID, outlet.id)
                putExtra(CheckInActivity.EXTRA_OUTLET_NAME, outlet.name)
            })
        }
        binding.outletList.adapter = adapter

        binding.logout.setOnClickListener {
            lifecycleScope.launch {
                authRepo.clearToken()
                startActivity(Intent(this@MainActivity, LoginActivity::class.java).apply { flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK })
                finish()
            }
        }
    }

    override fun onResume() {
        super.onResume()
        lifecycleScope.launch {
            val token = authRepo.getToken()
            if (token == null) {
                startActivity(Intent(this@MainActivity, LoginActivity::class.java))
                finish()
                return@launch
            }
            val baseUrl = authRepo.getBaseUrl()
            try {
                val api = RetrofitModule.apiService(baseUrl)
                val response = api.getOutlets("Bearer $token")
                if (response.isSuccessful) {
                    val list = response.body()!!.outlets
                    adapter?.update(list)
                    GeofenceHelper(this@MainActivity).registerOutlets(list)
                } else {
                    if (response.code() == 401) {
                        authRepo.clearToken()
                        startActivity(Intent(this@MainActivity, LoginActivity::class.java))
                        finish()
                    } else {
                        runOnUiThread { Toast.makeText(this@MainActivity, "Failed to load outlets", Toast.LENGTH_SHORT).show() }
                    }
                }
            } catch (e: Exception) {
                runOnUiThread { Toast.makeText(this@MainActivity, e.message ?: "Error", Toast.LENGTH_SHORT).show() }
            }
            val db = createAppDatabase(this@MainActivity)
            val pending = db.pendingCheckInDao().getAll()
            binding.pendingCount.text = "${pending.size} pending sync"
        }
    }
}
