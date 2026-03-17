package com.tajacore.distribution.ui.main

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.RecyclerView
import com.tajacore.distribution.data.api.dto.OutletDto
import com.tajacore.distribution.databinding.ItemOutletBinding

class OutletAdapter(
    private var outlets: List<OutletDto>,
    private val onOutletClick: (OutletDto) -> Unit
) : RecyclerView.Adapter<OutletAdapter.ViewHolder>() {

    fun update(list: List<OutletDto>) {
        outlets = list
        notifyDataSetChanged()
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder {
        val binding = ItemOutletBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return ViewHolder(binding)
    }

    override fun onBindViewHolder(holder: ViewHolder, position: Int) {
        holder.bind(outlets[position], onOutletClick)
    }

    override fun getItemCount() = outlets.size

    class ViewHolder(private val binding: ItemOutletBinding) : RecyclerView.ViewHolder(binding.root) {
        fun bind(outlet: OutletDto, onOutletClick: (OutletDto) -> Unit) {
            binding.outletName.text = outlet.name
            binding.outletAddress.text = outlet.address ?: outlet.code ?: ""
            binding.root.setOnClickListener { onOutletClick(outlet) }
        }
    }
}
