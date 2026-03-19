package com.taja.outlet.activity

import android.content.Intent
import android.os.Bundle
import android.view.View
import android.widget.Button
import android.widget.ProgressBar
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView
import com.taja.outlet.ApiClient
import com.taja.outlet.R
import com.taja.outlet.SessionManager
import com.taja.outlet.adapter.OutletListAdapter

class OutletListActivity : AppCompatActivity() {

    private lateinit var sessionManager: SessionManager
    private lateinit var adapter: OutletListAdapter
    private lateinit var progressBar: ProgressBar
    private lateinit var emptyText: TextView
    private lateinit var recycler: RecyclerView

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_outlet_list)
        sessionManager = SessionManager(this)
        if (!sessionManager.isLoggedIn) {
            Toast.makeText(this, R.string.error_session, Toast.LENGTH_SHORT).show()
            finish()
            return
        }
        findViewById<Button>(R.id.outlet_list_back).setOnClickListener { finish() }
        progressBar = findViewById(R.id.outlet_list_progress)
        emptyText = findViewById(R.id.outlet_list_empty)
        recycler = findViewById(R.id.outlet_list_recycler)
        recycler.layoutManager = LinearLayoutManager(this)
        adapter = OutletListAdapter(emptyList()) { outlet ->
            startActivity(Intent(this, OutletFormActivity::class.java).putExtra(OutletFormActivity.EXTRA_OUTLET_ID, outlet.id))
        }
        recycler.adapter = adapter
        loadOutlets()
        findViewById<View>(R.id.outlet_list_fab_add)?.setOnClickListener {
            startActivity(Intent(this, OutletFormActivity::class.java))
        }
    }

    private fun loadOutlets() {
        progressBar.visibility = View.VISIBLE
        emptyText.visibility = View.GONE
        recycler.visibility = View.VISIBLE
        Thread {
            val result = ApiClient.getOutlets(sessionManager.token ?: "")
            runOnUiThread {
                progressBar.visibility = View.GONE
                when (result) {
                    is ApiClient.ApiResult.Success -> {
                        adapter.setOutlets(result.data)
                        val empty = result.data.isEmpty()
                        emptyText.visibility = if (empty) View.VISIBLE else View.GONE
                        recycler.visibility = if (empty) View.GONE else View.VISIBLE
                    }
                    is ApiClient.ApiResult.Error -> {
                        Toast.makeText(this, result.message, Toast.LENGTH_LONG).show()
                        emptyText.visibility = View.VISIBLE
                        recycler.visibility = View.GONE
                    }
                }
            }
        }.start()
    }
}
