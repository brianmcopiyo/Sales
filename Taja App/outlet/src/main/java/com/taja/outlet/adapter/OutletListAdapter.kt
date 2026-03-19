package com.taja.outlet.adapter

import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.TextView
import androidx.recyclerview.widget.RecyclerView
import com.taja.outlet.ApiClient
import com.taja.outlet.R

class OutletListAdapter(
    private var outlets: List<ApiClient.Outlet>,
    private val onOutletClick: (ApiClient.Outlet) -> Unit
) : RecyclerView.Adapter<OutletListAdapter.ViewHolder>() {

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder {
        val v = LayoutInflater.from(parent.context).inflate(R.layout.item_outlet_row, parent, false)
        return ViewHolder(v, onOutletClick)
    }

    override fun onBindViewHolder(holder: ViewHolder, position: Int) {
        holder.bind(outlets[position])
    }

    override fun getItemCount(): Int = outlets.size

    fun setOutlets(list: List<ApiClient.Outlet>) {
        outlets = list
        notifyDataSetChanged()
    }

    class ViewHolder(
        itemView: View,
        private val onOutletClick: (ApiClient.Outlet) -> Unit
    ) : RecyclerView.ViewHolder(itemView) {
        private val nameText: TextView = itemView.findViewById(R.id.item_outlet_name)
        private val codeText: TextView = itemView.findViewById(R.id.item_outlet_code)
        private val addressText: TextView = itemView.findViewById(R.id.item_outlet_address)

        fun bind(outlet: ApiClient.Outlet) {
            nameText.text = outlet.name
            if (!outlet.code.isNullOrBlank()) {
                codeText.text = outlet.code
                codeText.visibility = View.VISIBLE
            } else {
                codeText.visibility = View.GONE
            }
            addressText.text = outlet.address ?: ""
            addressText.visibility = if (outlet.address.isNullOrBlank()) View.GONE else View.VISIBLE
            itemView.setOnClickListener { onOutletClick(outlet) }
        }
    }
}
