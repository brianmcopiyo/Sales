package com.kimaro.activity

import android.app.Activity
import android.os.Bundle
import android.widget.Button
import android.widget.TextView
import com.kimaro.R

class ReviewScannedImeisActivity : Activity() {

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_review_imei)

        val imeisText = intent.getStringExtra("imeis") ?: ""

        val titleView = findViewById<TextView>(R.id.review_imei_title)
        val subtitleView = findViewById<TextView>(R.id.review_imei_subtitle)
        val listView = findViewById<TextView>(R.id.review_imei_list)
        val cancelButton = findViewById<Button>(R.id.review_imei_cancel)
        val confirmButton = findViewById<Button>(R.id.review_imei_confirm)

        val lines = imeisText.split("\n").map { it.trim() }.filter { it.isNotEmpty() }
        val filteredLines = filterByMostCommonTac(lines)

        titleView.text = getString(R.string.review_imei_title_count, filteredLines.size)
        if (filteredLines.size < lines.size) {
            val removed = lines.size - filteredLines.size
            subtitleView.text = getString(R.string.review_imei_subtitle) + " " +
                getString(R.string.review_imei_filtered_subtitle, removed)
        } else {
            subtitleView.text = getString(R.string.review_imei_subtitle)
        }
        listView.text = filteredLines.joinToString("\n")

        cancelButton.setOnClickListener {
            setResult(Activity.RESULT_CANCELED)
            finish()
        }

        confirmButton.setOnClickListener {
            val data = intent
            data.putExtra("reviewed_imeis", filteredLines.joinToString("\n"))
            setResult(Activity.RESULT_OK, data)
            finish()
        }
    }

    /**
     * Keeps only IMEIs whose first 8 digits (TAC - Type Allocation Code) match the most common
     * pattern in the list. Same brand/model devices share the same TAC, so outliers are removed.
     */
    private fun filterByMostCommonTac(imeis: List<String>): List<String> {
        if (imeis.size <= 1) return imeis
        val withTac = imeis.filter { it.length >= 8 }
        if (withTac.isEmpty()) return imeis
        val tacCounts = withTac.groupingBy { it.take(8) }.eachCount()
        val mostCommonTac = tacCounts.maxByOrNull { it.value }?.key ?: return imeis
        return withTac.filter { it.take(8) == mostCommonTac }
    }
}

