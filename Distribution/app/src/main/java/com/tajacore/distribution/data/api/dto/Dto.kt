package com.tajacore.distribution.data.api.dto

import com.google.gson.annotations.SerializedName

data class LoginRequest(val login: String, val password: String)

data class LoginResponse(
    val token: String,
    val user: UserResponse
)

data class UserResponse(val id: String, val name: String, val email: String?)

data class OutletsResponse(val outlets: List<OutletDto>)

data class OutletDto(
    val id: String,
    val name: String,
    val code: String?,
    val address: String?,
    val lat: Double?,
    val lng: Double?,
    @SerializedName("geo_fence_type") val geoFenceType: String?,
    @SerializedName("geo_fence_radius_metres") val geoFenceRadiusMetres: Int?,
    @SerializedName("geo_fence_polygon") val geoFencePolygon: List<LatLng>?
)

data class LatLng(val lat: Double, val lng: Double)

data class SyncRequest(val items: List<SyncCheckInItem>)

data class SyncCheckInItem(
    @SerializedName("client_id") val clientId: String,
    @SerializedName("outlet_id") val outletId: String,
    val lat: Double,
    val lng: Double,
    val notes: String?,
    @SerializedName("photo_base64") val photoBase64: String?,
    @SerializedName("check_in_at") val checkInAt: String
)

data class SyncResponse(
    val synced: List<SyncedItem>,
    val failed: List<FailedItem>
)

data class SyncedItem(
    @SerializedName("client_id") val clientId: String,
    @SerializedName("server_id") val serverId: String
)

data class FailedItem(
    @SerializedName("client_id") val clientId: String,
    val message: String
)
