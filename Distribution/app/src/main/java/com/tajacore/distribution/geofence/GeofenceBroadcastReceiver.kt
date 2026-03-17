package com.tajacore.distribution.geofence

import android.app.NotificationManager
import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.os.Build
import androidx.core.app.NotificationCompat
import androidx.core.app.NotificationManagerCompat
import com.tajacore.distribution.R
import com.tajacore.distribution.ui.checkin.CheckInActivity
import com.tajacore.distribution.worker.CHANNEL_ID
import com.google.android.gms.location.Geofence
import com.google.android.gms.location.GeofenceStatusCodes
import com.google.android.gms.location.GeofencingEvent

/**
 * Receives geofence enter/exit events. On enter, shows a notification prompting "Check in at [Outlet]?"
 * Tapping the notification opens CheckInActivity for that outlet.
 */
class GeofenceBroadcastReceiver : BroadcastReceiver() {

    override fun onReceive(context: Context, intent: Intent) {
        val event = GeofencingEvent.fromIntent(intent) ?: return
        if (event.geofenceTransition != Geofence.GEOFENCE_TRANSITION_ENTER) return
        if (event.hasError()) {
            if (event.errorCode == GeofenceStatusCodes.GEOFENCE_NOT_AVAILABLE) return
            return
        }
        val triggeringGeofences = event.triggeringGeofences ?: return
        for (gf in triggeringGeofences) {
            val requestId = gf.requestId
            val parts = requestId.split(DELIMITER)
            if (parts.size >= 3) {
                val outletId = parts[1]
                val outletName = parts[2]
                showCheckInNotification(context, outletId, outletName)
            }
        }
    }

    private fun showCheckInNotification(context: Context, outletId: String, outletName: String) {
        val openIntent = Intent(context, CheckInActivity::class.java).apply {
            flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TOP
            putExtra(CheckInActivity.EXTRA_OUTLET_ID, outletId)
            putExtra(CheckInActivity.EXTRA_OUTLET_NAME, outletName)
        }
        val pendingIntent = android.app.PendingIntent.getActivity(
            context,
            outletId.hashCode(),
            openIntent,
            android.app.PendingIntent.FLAG_UPDATE_CURRENT or android.app.PendingIntent.FLAG_IMMUTABLE
        )
        val notification = NotificationCompat.Builder(context, CHANNEL_ID)
            .setSmallIcon(android.R.drawable.ic_menu_mylocation)
            .setContentTitle(context.getString(R.string.app_name))
            .setContentText(context.getString(R.string.check_in_at, outletName))
            .setContentIntent(pendingIntent)
            .setAutoCancel(true)
            .setPriority(NotificationCompat.PRIORITY_DEFAULT)
            .build()
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            if (context.getSystemService(NotificationManager::class.java).areNotificationsEnabled()) {
                NotificationManagerCompat.from(context).notify(outletId.hashCode(), notification)
            }
        } else {
            @Suppress("DEPRECATION")
            NotificationManagerCompat.from(context).notify(outletId.hashCode(), notification)
        }
    }

    companion object {
        const val DELIMITER = "::"
        fun geofenceRequestId(outletId: String, outletName: String) = "outlet${DELIMITER}$outletId$DELIMITER$outletName"
    }
}
