package com.tajacore.outletmap.data.api

import com.tajacore.outletmap.data.api.dto.LoginRequest
import com.tajacore.outletmap.data.api.dto.LoginResponse
import com.tajacore.outletmap.data.api.dto.OutletDto
import com.tajacore.outletmap.data.api.dto.OutletResponse
import com.tajacore.outletmap.data.api.dto.OutletsResponse
import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.Header
import retrofit2.http.POST
import retrofit2.http.PUT
import retrofit2.http.Path

interface ApiService {

    @POST("login")
    suspend fun login(@Body body: LoginRequest): Response<LoginResponse>

    @GET("outlets")
    suspend fun getOutlets(@Header("Authorization") bearer: String): Response<OutletsResponse>

    @GET("outlets/{id}")
    suspend fun getOutlet(
        @Header("Authorization") bearer: String,
        @Path("id") id: String
    ): Response<OutletResponse>

    @POST("outlets")
    suspend fun createOutlet(
        @Header("Authorization") bearer: String,
        @Body body: CreateOutletRequest
    ): Response<OutletResponse>

    @PUT("outlets/{id}")
    suspend fun updateOutlet(
        @Header("Authorization") bearer: String,
        @Path("id") id: String,
        @Body body: UpdateOutletRequest
    ): Response<OutletResponse>
}

data class CreateOutletRequest(
    val name: String,
    val lat: Double,
    val lng: Double,
    val address: String? = null,
    val geo_fence_type: String? = "radius",
    val geo_fence_radius_metres: Int? = null,
    val branch_id: String? = null,
    val is_active: Boolean = true
)

data class UpdateOutletRequest(
    val name: String? = null,
    val lat: Double? = null,
    val lng: Double? = null,
    val address: String? = null,
    val geo_fence_type: String? = null,
    val geo_fence_radius_metres: Int? = null,
    val is_active: Boolean? = null
)
