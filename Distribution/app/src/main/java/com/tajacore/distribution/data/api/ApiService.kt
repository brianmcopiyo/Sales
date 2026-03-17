package com.tajacore.distribution.data.api

import com.tajacore.distribution.data.api.dto.LoginRequest
import com.tajacore.distribution.data.api.dto.LoginResponse
import com.tajacore.distribution.data.api.dto.OutletsResponse
import com.tajacore.distribution.data.api.dto.SyncRequest
import com.tajacore.distribution.data.api.dto.SyncResponse
import okhttp3.MultipartBody
import okhttp3.RequestBody
import okhttp3.ResponseBody
import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.Header
import retrofit2.http.Multipart
import retrofit2.http.POST
import retrofit2.http.Part

interface ApiService {

    @POST("login")
    suspend fun login(@Body body: LoginRequest): Response<LoginResponse>

    @GET("user")
    suspend fun getCurrentUser(@Header("Authorization") bearer: String): Response<UserDto>

    @GET("outlets")
    suspend fun getOutlets(@Header("Authorization") bearer: String): Response<OutletsResponse>

    @Multipart
    @POST("check-ins")
    suspend fun submitCheckIn(
        @Header("Authorization") bearer: String,
        @Part("outlet_id") outletId: RequestBody,
        @Part("lat") lat: RequestBody,
        @Part("lng") lng: RequestBody,
        @Part("notes") notes: RequestBody,
        @Part photo: MultipartBody.Part?
    ): Response<CheckInResponse>

    @POST("sync/check-ins")
    suspend fun syncCheckIns(
        @Header("Authorization") bearer: String,
        @Body body: SyncRequest
    ): Response<SyncResponse>
}

data class UserDto(val id: String, val name: String, val email: String?)
data class CheckInResponse(val message: String, val check_in: CheckInDto)
data class CheckInDto(val id: String, val outlet_id: String, val check_in_at: String)
