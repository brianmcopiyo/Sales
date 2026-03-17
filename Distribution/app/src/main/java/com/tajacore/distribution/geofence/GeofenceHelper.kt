package com.tajacore.distribution.geofence

import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import com.google.android.gms.location.Geofence
import com.google.android.gms.location.GeofencingClient
import com.google.android.gms.location.GeofencingRequest
import com.google.android.gms.location.LocationServices
import com.tajacore.distribution.data.api.dto.OutletDto

/**
 * Registers geofences for outlets that have a radius. When the device enters, GeofenceBroadcastReceiver fires.
 */
class GeofenceHelper(private val context: Context) {

    private val client: GeofencingClient = LocationServices.getGeofencingClient(context)

    fun registerOutlets(outlets: List<OutletDto>) {
        val lat = outlets.filter { it.lat != null && it.lng != null && it.geoFenceType == "radius" && (it.geoFenceRadiusMetres ?: 0) > 0 }
        if (lat.isEmpty()) return
        val geofences = lat.mapNotNull { o ->
            val radius = (o.geoFenceRadiusMetres ?: 100).toFloat().coerceIn(50f, 500f)
            Geofence.Builder()
                .setRequestId(GeofenceBroadcastReceiver.geofenceRequestId(o.id, o.name))
                .setCircularRegion(o.lat!!, o.lng!!, radius)
                .setExpirationDuration(Geofence.NEVER_EXPIRE)
                .setTransitionTypes(Geofence.GEOFENCE_TRANSITION_ENTER)
                .build()
        }
        if (geofences.isEmpty()) return
        val request = GeofencingRequest.Builder()
            .setInitialTrigger(GeofencingRequest.INITIAL_TRIGGER_ENTER)
            .addGeofences(geofences)
            .build()
        val intent = Intent(context, GeofenceBroadcastReceiver::class.java)
        val pending = PendingIntent.getBroadcast(
            context,
            0,
            intent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )
        client.addGeofences(request, pending).addOnFailureListener { }.addOnSuccessListener { }
    }
}
