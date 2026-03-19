package com.taja.app.adapter

import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.TextView
import androidx.recyclerview.widget.RecyclerView
import com.taja.app.ApiClient
import com.taja.app.R

/**
 * RecyclerView adapter for outlet list. Click on item invokes [onOutletClick].
 */
class OutletsAdapter(
    private var outlets: List<ApiClient.Outlet>,
    private val onOutletClick: (ApiClient.Outlet) -> Unit
) : RecyclerView.Adapter<OutletsAdapter.ViewHolder>() {

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder {
        val view = LayoutInflater.from(parent.context).inflate(R.layout.item_outlet, parent, false)
        return ViewHolder(view)
    }

    override fun onBindViewHolder(holder: ViewHolder, position: Int) {
        val outlet = outlets[position]
        holder.nameText.text = outlet.name
        holder.codeText.text = outlet.code ?: ""
        holder.codeText.visibility = if (!outlet.code.isNullOrBlank()) View.VISIBLE else View.GONE
        holder.addressText.text = outlet.address ?: ""
        holder.itemView.setOnClickListener { onOutletClick(outlet) }
    }

    override fun getItemCount(): Int = outlets.size

    fun setOutlets(newList: List<ApiClient.Outlet>) {
        outlets = newList
        notifyDataSetChanged()
    }

    class ViewHolder(itemView: View) : RecyclerView.ViewHolder(itemView) {
        val nameText: TextView = itemView.findViewById(R.id.item_outlet_name)
        val codeText: TextView = itemView.findViewById(R.id.item_outlet_code)
        val addressText: TextView = itemView.findViewById(R.id.item_outlet_address)
    }
}
