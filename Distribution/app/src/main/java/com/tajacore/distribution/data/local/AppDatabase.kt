package com.tajacore.distribution.data.local

import android.content.Context
import androidx.room.Database
import androidx.room.Room
import androidx.room.RoomDatabase

@Database(entities = [PendingCheckIn::class], version = 1, exportSchema = false)
abstract class AppDatabase : RoomDatabase() {
    abstract fun pendingCheckInDao(): PendingCheckInDao
}

fun createAppDatabase(context: Context): AppDatabase =
    Room.databaseBuilder(context, AppDatabase::class.java, "distribution.db").build()
