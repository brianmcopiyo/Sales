package com.taja.outlet.activity

import android.content.Intent
import android.os.Bundle
import androidx.appcompat.app.AppCompatActivity
import com.taja.outlet.R

class OutletMapActivity : AppCompatActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_outlet_map)
        findViewById<android.widget.Button>(R.id.outlet_map_btn_list).setOnClickListener {
            startActivity(Intent(this, OutletListActivity::class.java))
        }
    }
}
