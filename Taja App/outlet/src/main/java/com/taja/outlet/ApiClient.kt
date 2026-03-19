package com.taja.outlet

import okhttp3.MediaType.Companion.toMediaType
import okhttp3.OkHttpClient
import okhttp3.Request
import okhttp3.RequestBody.Companion.toRequestBody
import org.json.JSONObject
import java.util.concurrent.TimeUnit

/**
 * API client for outlet mapping app. Same backend as com.taja.app.
 */
object ApiClient {

    private val jsonType = "application/json; charset=utf-8".toMediaType()
    private val client = OkHttpClient.Builder()
        .connectTimeout(30, TimeUnit.SECONDS)
        .readTimeout(30, TimeUnit.SECONDS)
        .writeTimeout(30, TimeUnit.SECONDS)
        .build()

    private fun baseUrl(): String = com.taja.outlet.BuildConfig.API_BASE_URL.trimEnd('/')

    sealed class LoginResult {
        data class RequiresOtp(val pendingToken: String, val message: String) : LoginResult()
        data class Success(val token: String, val user: UserInfo) : LoginResult()
    }

    data class UserInfo(val id: String, val name: String, val email: String, val phone: String, val branchId: String?)

    data class Outlet(
        val id: String,
        val name: String,
        val code: String?,
        val address: String?,
        val lat: Double?,
        val lng: Double?,
        val geoFenceType: String?,
        val geoFenceRadiusMetres: Int?,
        val geoFencePolygon: String?,
        val branchId: String?,
        val isActive: Boolean?
    )

    sealed class ApiResult<out T> {
        data class Success<T>(val data: T) : ApiResult<T>()
        data class Error(val message: String, val code: Int) : ApiResult<Nothing>()
    }

    fun login(login: String, password: String): ApiResult<LoginResult> {
        val body = JSONObject().apply {
            put("login", login)
            put("password", password)
        }.toString().toRequestBody(jsonType)
        val request = Request.Builder()
            .url("${baseUrl()}/api/login")
            .post(body)
            .addHeader("Accept", "application/json")
            .build()
        return execute(request) { json ->
            if (json.optBoolean("requires_otp", false)) {
                LoginResult.RequiresOtp(
                    pendingToken = json.getString("pending_token"),
                    message = json.optString("message", "Enter the OTP sent to your email or phone.")
                )
            } else {
                val u = json.getJSONObject("user")
                LoginResult.Success(
                    token = json.getString("token"),
                    user = UserInfo(
                        id = u.optString("id", ""),
                        name = u.optString("name", ""),
                        email = u.optString("email", ""),
                        phone = u.optString("phone", ""),
                        branchId = u.optString("branch_id", "").takeIf { it.isNotEmpty() }
                    )
                )
            }
        }
    }

    fun verifyOtp(pendingToken: String, otp: String): ApiResult<LoginResult.Success> {
        val body = JSONObject().apply { put("otp", otp) }.toString().toRequestBody(jsonType)
        val request = Request.Builder()
            .url("${baseUrl()}/api/verify-otp")
            .post(body)
            .addHeader("Authorization", "Bearer $pendingToken")
            .addHeader("Accept", "application/json")
            .addHeader("Content-Type", "application/json")
            .build()
        return execute(request) { json ->
            val u = json.getJSONObject("user")
            LoginResult.Success(
                token = json.getString("token"),
                user = UserInfo(
                    id = u.optString("id", ""),
                    name = u.optString("name", ""),
                    email = u.optString("email", ""),
                    phone = u.optString("phone", ""),
                    branchId = u.optString("branch_id", "").takeIf { it.isNotEmpty() }
                )
            )
        }
    }

    fun resendOtp(pendingToken: String): ApiResult<String> {
        val body = JSONObject().toString().toRequestBody(jsonType)
        val request = Request.Builder()
            .url("${baseUrl()}/api/resend-otp")
            .post(body)
            .addHeader("Authorization", "Bearer $pendingToken")
            .addHeader("Accept", "application/json")
            .addHeader("Content-Type", "application/json")
            .build()
        return execute(request) { json -> json.optString("message", "OTP resent.") }
    }

    fun getOutlets(token: String): ApiResult<List<Outlet>> {
        val request = Request.Builder()
            .url("${baseUrl()}/api/outlets")
            .addHeader("Authorization", "Bearer $token")
            .addHeader("Accept", "application/json")
            .get()
            .build()
        return execute(request) { json ->
            val arr = json.getJSONArray("outlets")
            (0 until arr.length()).map { i -> parseOutlet(arr.getJSONObject(i)) }
        }
    }

    fun getOutlet(token: String, outletId: String): ApiResult<Outlet> {
        val request = Request.Builder()
            .url("${baseUrl()}/api/outlets/$outletId")
            .addHeader("Authorization", "Bearer $token")
            .addHeader("Accept", "application/json")
            .get()
            .build()
        return execute(request) { json -> parseOutlet(json.getJSONObject("outlet")) }
    }

    fun createOutlet(
        token: String,
        name: String,
        code: String?,
        address: String?,
        lat: Double,
        lng: Double
    ): ApiResult<Outlet> {
        val body = JSONObject().apply {
            put("name", name)
            code?.takeIf { it.isNotBlank() }?.let { put("code", it) }
            address?.takeIf { it.isNotBlank() }?.let { put("address", it) }
            put("lat", lat)
            put("lng", lng)
        }.toString().toRequestBody(jsonType)
        val request = Request.Builder()
            .url("${baseUrl()}/api/outlets")
            .addHeader("Authorization", "Bearer $token")
            .addHeader("Accept", "application/json")
            .addHeader("Content-Type", "application/json")
            .post(body)
            .build()
        return execute(request) { json -> parseOutlet(json.getJSONObject("outlet")) }
    }

    fun updateOutlet(
        token: String,
        outletId: String,
        name: String,
        code: String?,
        address: String?,
        lat: Double?,
        lng: Double?
    ): ApiResult<Outlet> {
        val body = JSONObject().apply {
            put("name", name)
            if (code != null) put("code", code) else put("code", "")
            if (address != null) put("address", address) else put("address", "")
            lat?.let { put("lat", it) }
            lng?.let { put("lng", it) }
        }.toString().toRequestBody(jsonType)
        val request = Request.Builder()
            .url("${baseUrl()}/api/outlets/$outletId")
            .addHeader("Authorization", "Bearer $token")
            .addHeader("Accept", "application/json")
            .addHeader("Content-Type", "application/json")
            .put(body)
            .build()
        return execute(request) { json -> parseOutlet(json.getJSONObject("outlet")) }
    }

    private fun parseOutlet(o: org.json.JSONObject): Outlet = Outlet(
        id = o.optString("id", ""),
        name = o.optString("name", ""),
        code = o.optString("code", "").takeIf { it.isNotEmpty() },
        address = o.optString("address", "").takeIf { it.isNotEmpty() },
        lat = o.optDouble("lat", Double.NaN).takeIf { !it.isNaN() },
        lng = o.optDouble("lng", Double.NaN).takeIf { !it.isNaN() },
        geoFenceType = o.optString("geo_fence_type", "").takeIf { it.isNotEmpty() },
        geoFenceRadiusMetres = o.optInt("geo_fence_radius_metres", 0).takeIf { it > 0 },
        geoFencePolygon = o.optString("geo_fence_polygon", "").takeIf { it.isNotEmpty() },
        branchId = o.optString("branch_id", "").takeIf { it.isNotEmpty() },
        isActive = if (o.has("is_active")) o.optBoolean("is_active", true) else null
    )

    private fun <T> execute(request: Request, parse: (JSONObject) -> T): ApiResult<T> {
        return try {
            client.newCall(request).execute().use { response ->
                val bodyStr = response.body?.string() ?: ""
                if (!response.isSuccessful) {
                    val msg = try {
                        JSONObject(bodyStr).optString("message", "Request failed")
                    } catch (_: Exception) {
                        "Request failed: ${response.code}"
                    }
                    return ApiResult.Error(msg, response.code)
                }
                try {
                    val json = JSONObject(bodyStr)
                    ApiResult.Success(parse(json))
                } catch (e: Exception) {
                    ApiResult.Error("Invalid response: ${e.message}", 0)
                }
            }
        } catch (e: Exception) {
            ApiResult.Error(e.message ?: "Network error", 0)
        }
    }
}
