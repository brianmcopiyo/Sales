package com.tajacore.outletmap.data.api.dto

import com.google.gson.annotations.SerializedName

data class LoginRequest(val login: String, val password: String)

data class LoginResponse(
    val token: String,
    val user: UserResponse
)

data class UserResponse(val id: String, val name: String, val email: String?)

data class OutletsResponse(val outlets: List<OutletDto>)

data class OutletResponse(val outlet: OutletDto)

data class OutletDto(
    val id: String,
    val name: String,
    val code: String?,
    val address: String?,
    val lat: Double?,
    val lng: Double?,
    @SerializedName("geo_fence_type") val geoFenceType: String?,
    @SerializedName("geo_fence_radius_metres") val geoFenceRadiusMetres: Int?,
    @SerializedName("geo_fence_polygon") val geoFencePolygon: List<Any>?,
    @SerializedName("branch_id") val branchId: String?,
    @SerializedName("is_active") val isActive: Boolean?
)
