package com.tajacore.distribution.ui.main

import android.content.Intent
import android.os.Bundle
import android.view.Menu
import android.view.MenuItem
import android.view.View
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView
import com.tajacore.distribution.R
import com.tajacore.distribution.data.AuthRepository
import com.tajacore.distribution.data.api.RetrofitModule
import com.tajacore.distribution.data.api.dto.OutletDto
import com.tajacore.distribution.data.local.createAppDatabase
import com.tajacore.distribution.geofence.GeofenceHelper
import com.tajacore.distribution.ui.checkin.CheckInActivity
import com.tajacore.distribution.ui.login.LoginActivity
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext

class MainActivity : AppCompatActivity() {

    private lateinit var authRepo: AuthRepository
    private lateinit var pendingSyncText: android.widget.TextView
    private lateinit var outletList: RecyclerView
    private var outletAdapter: OutletAdapter? = null

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_main)
        authRepo = AuthRepository(this)

        lifecycleScope.launch {
            val token = authRepo.getToken()
            if (token == null) {
                startActivity(Intent(this@MainActivity, LoginActivity::class.java))
                finish()
                return@launch
            }
            setSupportActionBar(findViewById(R.id.main_toolbar))
            pendingSyncText = findViewById(R.id.main_pending_sync)
            outletList = findViewById(R.id.main_outlet_list)
            outletList.layoutManager = LinearLayoutManager(this@MainActivity)
            outletAdapter = OutletAdapter(emptyList()) { outlet ->
                startActivity(Intent(this@MainActivity, CheckInActivity::class.java).apply {
                    putExtra(CheckInActivity.EXTRA_OUTLET_ID, outlet.id)
                    putExtra(CheckInActivity.EXTRA_OUTLET_NAME, outlet.name)
                })
            }
            outletList.adapter = outletAdapter

            loadOutletsAndPending {
                startActivity(Intent(this@MainActivity, LoginActivity::class.java).setFlags(Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK))
                finish()
            }
        }
    }

    override fun onCreateOptionsMenu(menu: Menu): Boolean {
        menuInflater.inflate(R.menu.menu_main, menu)
        return true
    }

    override fun onOptionsItemSelected(item: MenuItem): Boolean {
        if (item.itemId == R.id.action_logout) {
            lifecycleScope.launch {
                authRepo.clearToken()
                startActivity(Intent(this@MainActivity, LoginActivity::class.java).setFlags(Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK))
                finish()
            }
            return true
        }
        return super.onOptionsItemSelected(item)
    }

    override fun onResume() {
        super.onResume()
        lifecycleScope.launch {
            if (authRepo.getToken() != null) {
                loadOutletsAndPendingSilent()
            }
        }
    }

    private fun loadOutletsAndPending(onUnauthorized: () -> Unit) {
        lifecycleScope.launch {
            val token = authRepo.getToken() ?: run {
                withContext(Dispatchers.Main) { onUnauthorized() }
                return@launch
            }
            val baseUrl = authRepo.getBaseUrl()
            try {
                val api = RetrofitModule.apiService(baseUrl)
                val response = api.getOutlets("Bearer $token")
                if (response.isSuccessful) {
                    val list = response.body()!!.outlets
                    withContext(Dispatchers.Main) {
                        outletAdapter?.setOutlets(list)
                        outletAdapter?.notifyDataSetChanged()
                        GeofenceHelper(this@MainActivity).registerOutlets(list)
                    }
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
            withContext(Dispatchers.Main) {
                if (pending.isNotEmpty()) {
                    pendingSyncText.visibility = View.VISIBLE
                    pendingSyncText.text = "${pending.size} pending sync"
                } else {
                    pendingSyncText.visibility = View.GONE
                }
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
                    withContext(Dispatchers.Main) {
                        outletAdapter?.setOutlets(list)
                        outletAdapter?.notifyDataSetChanged()
                        GeofenceHelper(this@MainActivity).registerOutlets(list)
                    }
                }
            } catch (_: Exception) { }
            val db = createAppDatabase(this@MainActivity)
            val pending = db.pendingCheckInDao().getAll()
            withContext(Dispatchers.Main) {
                if (pending.isNotEmpty()) {
                    pendingSyncText.visibility = View.VISIBLE
                    pendingSyncText.text = "${pending.size} pending sync"
                } else {
                    pendingSyncText.visibility = View.GONE
                }
            }
        }
    }
}

private class OutletAdapter(
    private var outlets: List<OutletDto>,
    private val onItemClick: (OutletDto) -> Unit
) : RecyclerView.Adapter<OutletAdapter.VH>() {

    fun setOutlets(list: List<OutletDto>) {
        outlets = list
    }

    override fun onCreateViewHolder(parent: android.view.ViewGroup, viewType: Int): VH {
        val v = android.view.LayoutInflater.from(parent.context).inflate(R.layout.item_outlet, parent, false)
        return VH(v)
    }

    override fun onBindViewHolder(holder: VH, position: Int) {
        val o = outlets[position]
        holder.name.text = o.name
        val sub = o.address ?: o.code ?: ""
        if (sub.isNotEmpty()) {
            holder.subtitle.visibility = View.VISIBLE
            holder.subtitle.text = sub
        } else {
            holder.subtitle.visibility = View.GONE
        }
        holder.itemView.setOnClickListener { onItemClick(o) }
    }

    override fun getItemCount(): Int = outlets.size

    class VH(itemView: android.view.View) : RecyclerView.ViewHolder(itemView) {
        val name: android.widget.TextView = itemView.findViewById(R.id.item_outlet_name)
        val subtitle: android.widget.TextView = itemView.findViewById(R.id.item_outlet_subtitle)
    }
}
