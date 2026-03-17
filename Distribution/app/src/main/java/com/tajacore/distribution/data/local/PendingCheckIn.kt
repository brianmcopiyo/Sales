package com.tajacore.distribution.data.local

import androidx.room.Entity
import androidx.room.PrimaryKey

@Entity(tableName = "pending_check_ins")
data class PendingCheckIn(
    @PrimaryKey val clientId: String,
    val outletId: String,
    val outletName: String,
    val lat: Double,
    val lng: Double,
    val notes: String?,
    val photoPath: String?,
    val checkInAt: String,
    val createdAt: Long = System.currentTimeMillis()
)
