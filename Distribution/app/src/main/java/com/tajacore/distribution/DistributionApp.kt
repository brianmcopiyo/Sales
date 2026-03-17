package com.tajacore.distribution

import android.app.Application
import android.app.NotificationChannel
import android.app.NotificationManager
import android.os.Build

class DistributionApp : Application() {
    override fun onCreate() {
        super.onCreate()
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val channel = NotificationChannel(
                com.tajacore.distribution.worker.CHANNEL_ID,
                "Check-in reminders",
                NotificationManager.IMPORTANCE_DEFAULT
            )
            getSystemService(NotificationManager::class.java).createNotificationChannel(channel)
        }
    }
}
