package com.taja.app

import okhttp3.MediaType.Companion.toMediaType
import okhttp3.MultipartBody
import okhttp3.OkHttpClient
import okhttp3.Request
import okhttp3.RequestBody.Companion.asRequestBody
import okhttp3.RequestBody.Companion.toRequestBody
import org.json.JSONArray
import org.json.JSONObject
import java.io.File
import java.util.concurrent.TimeUnit

object ApiClient {

    private val jsonType = "application/json; charset=utf-8".toMediaType()
    private val client = OkHttpClient.Builder()
        .connectTimeout(30, TimeUnit.SECONDS)
        .readTimeout(30, TimeUnit.SECONDS)
        .writeTimeout(30, TimeUnit.SECONDS)
        .build()

    private fun baseUrl(): String = com.taja.app.BuildConfig.API_BASE_URL.trimEnd('/')

    /** Result of login: either OTP required (pending_token) or direct success (token + user). */
    sealed class LoginResult {
        data class RequiresOtp(val pendingToken: String, val message: String) : LoginResult()
        data class Success(val token: String, val user: UserInfo) : LoginResult()
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
                val userObj = json.getJSONObject("user")
                LoginResult.Success(
                    token = json.getString("token"),
                    user = UserInfo(
                        id = userObj.optString("id", ""),
                        name = userObj.optString("name", ""),
                        email = userObj.optString("email", ""),
                        phone = userObj.optString("phone", ""),
                        branchId = userObj.optString("branch_id", "").takeIf { it.isNotEmpty() }
                    )
                )
            }
        }
    }

    fun verifyOtp(pendingToken: String, otp: String): ApiResult<LoginResponse> {
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
            LoginResponse(
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

        return execute(request) { json ->
            json.optString("message", "OTP resent.")
        }
    }

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

    sealed class ApiResult<out T> {
        data class Success<T>(val data: T) : ApiResult<T>()
        data class Error(val message: String, val code: Int) : ApiResult<Nothing>()
    }

    data class LoginResponse(val token: String, val user: UserInfo)
    data class UserInfo(val id: String, val name: String, val email: String, val phone: String, val branchId: String?)
    data class User(
        val id: String,
        val name: String,
        val email: String?,
        val phone: String?,
        val branchId: String?,
        val branch: Branch?
    )
    data class Branch(val id: String, val name: String)

    data class DashboardSummary(
        val outletsCount: Int,
        val checkInsToday: Int,
        val checkInsThisWeek: Int,
        val visitedOutletsToday: Int,
        val coverageTodayPercent: Double,
        val openCheckIns: Int
    )

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

    data class CheckInCreated(val id: String, val outletId: String, val checkInAt: String)
    data class CheckOutResult(val id: String, val outletId: String, val checkInAt: String, val checkOutAt: String?)

    data class SyncCheckInItem(
        val clientId: String,
        val outletId: String,
        val lat: Double,
        val lng: Double,
        val notes: String?,
        val photoBase64: String?,
        val checkInAt: String
    )
    data class SyncCheckInsResponse(
        val synced: List<SyncedItem>,
        val failed: List<FailedItem>
    )
    data class SyncedItem(val clientId: String, val serverId: String)
    data class FailedItem(val clientId: String, val message: String)

    fun getUser(token: String): ApiResult<User> {
        val request = Request.Builder()
            .url("${baseUrl()}/api/user")
            .addHeader("Authorization", "Bearer $token")
            .addHeader("Accept", "application/json")
            .get()
            .build()
        return execute(request) { json ->
            val branchObj = json.optJSONObject("branch")
            User(
                id = json.optString("id", ""),
                name = json.optString("name", ""),
                email = json.optString("email", "").takeIf { it.isNotEmpty() },
                phone = json.optString("phone", "").takeIf { it.isNotEmpty() },
                branchId = json.optString("branch_id", "").takeIf { it.isNotEmpty() },
                branch = branchObj?.let { Branch(it.optString("id", ""), it.optString("name", "")) }
            )
        }
    }

    fun getDashboardSummary(token: String): ApiResult<DashboardSummary> {
        val request = Request.Builder()
            .url("${baseUrl()}/api/dashboard-summary")
            .addHeader("Authorization", "Bearer $token")
            .addHeader("Accept", "application/json")
            .get()
            .build()
        return execute(request) { json ->
            DashboardSummary(
                outletsCount = json.optInt("outlets_count", 0),
                checkInsToday = json.optInt("check_ins_today", 0),
                checkInsThisWeek = json.optInt("check_ins_this_week", 0),
                visitedOutletsToday = json.optInt("visited_outlets_today", 0),
                coverageTodayPercent = json.optDouble("coverage_today_percent", 0.0),
                openCheckIns = json.optInt("open_check_ins", 0)
            )
        }
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
        return execute(request) { json ->
            parseOutlet(json.getJSONObject("outlet"))
        }
    }

    private fun parseOutlet(o: JSONObject): Outlet = Outlet(
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

    fun createCheckIn(
        token: String,
        outletId: String,
        lat: Double,
        lng: Double,
        notes: String? = null,
        photoFile: File? = null
    ): ApiResult<CheckInCreated> {
        val bodyBuilder = MultipartBody.Builder().setType(MultipartBody.FORM)
            .addFormDataPart("outlet_id", outletId)
            .addFormDataPart("lat", lat.toString())
            .addFormDataPart("lng", lng.toString())
        notes?.takeIf { it.isNotBlank() }?.let { bodyBuilder.addFormDataPart("notes", it) }
        photoFile?.takeIf { it.exists() }?.let { file ->
            bodyBuilder.addFormDataPart(
                "photo",
                file.name,
                file.asRequestBody("image/jpeg".toMediaType())
            )
        }
        val body = bodyBuilder.build()
        val request = Request.Builder()
            .url("${baseUrl()}/api/check-ins")
            .addHeader("Authorization", "Bearer $token")
            .addHeader("Accept", "application/json")
            .post(body)
            .build()
        return execute(request) { json ->
            val c = json.getJSONObject("check_in")
            CheckInCreated(
                id = c.optString("id", ""),
                outletId = c.optString("outlet_id", ""),
                checkInAt = c.optString("check_in_at", "")
            )
        }
    }

    fun syncCheckIns(token: String, items: List<SyncCheckInItem>): ApiResult<SyncCheckInsResponse> {
        val arr = JSONArray()
        items.forEach { item ->
            arr.put(JSONObject().apply {
                put("client_id", item.clientId)
                put("outlet_id", item.outletId)
                put("lat", item.lat)
                put("lng", item.lng)
                put("notes", item.notes ?: "")
                item.photoBase64?.let { put("photo_base64", it) }
                put("check_in_at", item.checkInAt)
            })
        }
        val body = JSONObject().apply { put("items", arr) }.toString().toRequestBody(jsonType)
        val request = Request.Builder()
            .url("${baseUrl()}/api/sync/check-ins")
            .addHeader("Authorization", "Bearer $token")
            .addHeader("Accept", "application/json")
            .addHeader("Content-Type", "application/json")
            .post(body)
            .build()
        return execute(request) { json ->
            val syncedArr = json.optJSONArray("synced") ?: JSONArray()
            val failedArr = json.optJSONArray("failed") ?: JSONArray()
            SyncCheckInsResponse(
                synced = (0 until syncedArr.length()).map { i ->
                    val o = syncedArr.getJSONObject(i)
                    SyncedItem(o.optString("client_id", ""), o.optString("server_id", ""))
                },
                failed = (0 until failedArr.length()).map { i ->
                    val o = failedArr.getJSONObject(i)
                    FailedItem(o.optString("client_id", ""), o.optString("message", ""))
                }
            )
        }
    }

    fun checkOut(
        token: String,
        checkInId: String,
        lat: Double,
        lng: Double,
        notes: String? = null
    ): ApiResult<CheckOutResult> {
        val body = JSONObject().apply {
            put("lat", lat)
            put("lng", lng)
            notes?.let { put("notes", it) }
        }.toString().toRequestBody(jsonType)
        val request = Request.Builder()
            .url("${baseUrl()}/api/check-ins/$checkInId/check-out")
            .addHeader("Authorization", "Bearer $token")
            .addHeader("Accept", "application/json")
            .addHeader("Content-Type", "application/json")
            .post(body)
            .build()
        return execute(request) { json ->
            val c = json.getJSONObject("check_in")
            CheckOutResult(
                id = c.optString("id", ""),
                outletId = c.optString("outlet_id", ""),
                checkInAt = c.optString("check_in_at", ""),
                checkOutAt = c.optString("check_out_at", "").takeIf { it.isNotBlank() }
            )
        }
    }

    data class PlannedVisit(
        val id: String,
        val outletId: String,
        val plannedDate: String?,
        val sequence: Int
    )

    fun getPlannedVisits(token: String, date: String? = null): ApiResult<List<PlannedVisit>> {
        val url = if (date.isNullOrBlank()) {
            "${baseUrl()}/api/planned-visits"
        } else {
            "${baseUrl()}/api/planned-visits?planned_date=$date"
        }
        val request = Request.Builder()
            .url(url)
            .addHeader("Authorization", "Bearer $token")
            .addHeader("Accept", "application/json")
            .get()
            .build()
        return execute(request) { json ->
            val arr = json.optJSONArray("planned_visits") ?: JSONArray()
            (0 until arr.length()).map { i ->
                val o = arr.getJSONObject(i)
                PlannedVisit(
                    id = o.optString("id", ""),
                    outletId = o.optString("outlet_id", ""),
                    plannedDate = o.optString("planned_date", "").takeIf { it.isNotBlank() },
                    sequence = o.optInt("sequence", 0)
                )
            }
        }
    }
}
