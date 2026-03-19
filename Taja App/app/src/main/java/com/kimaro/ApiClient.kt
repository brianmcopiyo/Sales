package com.kimaro

import okhttp3.MediaType.Companion.toMediaType
import okhttp3.OkHttpClient
import okhttp3.Request
import okhttp3.RequestBody.Companion.toRequestBody
import org.json.JSONObject
import java.util.concurrent.TimeUnit

/**
 * Simple API client for login and dashboard. Uses OkHttp; token is set per-request.
 */
object ApiClient {

    private val jsonType = "application/json; charset=utf-8".toMediaType()
    private val client = OkHttpClient.Builder()
        .connectTimeout(30, TimeUnit.SECONDS)
        .readTimeout(30, TimeUnit.SECONDS)
        .writeTimeout(30, TimeUnit.SECONDS)
        .build()

    private fun baseUrl(): String = com.kimaro.BuildConfig.API_BASE_URL.trimEnd('/')

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
        val body = JSONObject().apply {
            put("pending_token", pendingToken)
            put("otp", otp)
        }.toString().toRequestBody(jsonType)

        val request = Request.Builder()
            .url("${baseUrl()}/api/login/verify-otp")
            .post(body)
            .addHeader("Accept", "application/json")
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
        val body = JSONObject().apply { put("pending_token", pendingToken) }
            .toString().toRequestBody(jsonType)

        val request = Request.Builder()
            .url("${baseUrl()}/api/login/resend-otp")
            .post(body)
            .addHeader("Accept", "application/json")
            .build()

        return execute(request) { json ->
            json.optString("message", "OTP resent.")
        }
    }

    fun getDashboard(token: String): ApiResult<DashboardResponse> {
        val request = Request.Builder()
            .url("${baseUrl()}/api/dashboard")
            .addHeader("Authorization", "Bearer $token")
            .addHeader("Accept", "application/json")
            .get()
            .build()

        return execute(request) { json ->
            val userObj = json.getJSONObject("user")
            val statsObj = json.getJSONObject("stats")
            val branchObj = userObj.optJSONObject("branch")
            DashboardResponse(
                user = UserInfo(
                    id = userObj.optString("id", ""),
                    name = userObj.optString("name", ""),
                    email = userObj.optString("email", ""),
                    phone = userObj.optString("phone", ""),
                    branchId = branchObj?.optString("id")?.takeIf { it.isNotEmpty() }
                ),
                branchName = branchObj?.optString("name"),
                stats = DashboardStats(
                    totalSales = statsObj.optInt("total_sales", 0),
                    salesThisMonth = statsObj.optInt("sales_this_month", 0),
                    salesToday = statsObj.optInt("sales_today", 0),
                    totalRevenue = statsObj.optDouble("total_revenue", 0.0),
                    revenueThisMonth = statsObj.optDouble("revenue_this_month", 0.0),
                    openTickets = statsObj.optInt("open_tickets", 0),
                    totalTickets = statsObj.optInt("total_tickets", 0),
                    pendingTransfers = statsObj.optInt("pending_transfers", 0),
                    lowStockItems = statsObj.optInt("low_stock_items", 0),
                    outOfStockItems = statsObj.optInt("out_of_stock_items", 0),
                    totalDevices = statsObj.optInt("total_devices", 0),
                    availableDevices = statsObj.optInt("available_devices", 0),
                    pendingRestockOrders = statsObj.optInt("pending_restock_orders", 0),
                    pendingStockTakes = statsObj.optInt("pending_stock_takes", 0)
                )
            )
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
    data class DashboardResponse(val user: UserInfo, val branchName: String?, val stats: DashboardStats)
    data class DashboardStats(
        val totalSales: Int,
        val salesThisMonth: Int,
        val salesToday: Int,
        val totalRevenue: Double,
        val revenueThisMonth: Double,
        val openTickets: Int,
        val totalTickets: Int,
        val pendingTransfers: Int,
        val lowStockItems: Int,
        val outOfStockItems: Int,
        val totalDevices: Int,
        val availableDevices: Int,
        val pendingRestockOrders: Int,
        val pendingStockTakes: Int
    )

    // --- Restock wizard ---
    data class RestockBranch(val id: String, val name: String, val code: String)
    data class RestockProduct(val id: String, val name: String, val sku: String)
    data class RestockBranchesResponse(val branches: List<RestockBranch>)
    data class RestockProductsResponse(val products: List<RestockProduct>)
    data class RestockOrderCreatedResponse(
        val message: String,
        val order_batch: String?,
        val order: RestockOrderItem?,
        val orders: List<RestockOrderItem>?
    )
    data class RestockOrderItem(val id: String, val order_number: String, val product_id: String, val product_name: String?, val quantity_ordered: Int)

    /** Pending restock order (awaiting approval). */
    data class PendingRestockOrder(
        val id: String,
        val order_number: String,
        val order_batch: String?,
        val branch_id: String,
        val branch_name: String?,
        val branch_code: String?,
        val product_id: String,
        val product_name: String?,
        val product_sku: String?,
        val quantity_ordered: Int,
        val quantity_received: Int,
        val quantity_outstanding: Int,
        val reference_number: String?,
        val dealership_name: String?,
        val expected_at: String?,
        val ordered_at: String?,
        val created_by_name: String?
    )
    data class PendingRestockOrdersResponse(val orders: List<PendingRestockOrder>)

    fun getRestockBranches(token: String): ApiResult<RestockBranchesResponse> {
        val request = Request.Builder()
            .url("${baseUrl()}/api/restock/branches")
            .addHeader("Authorization", "Bearer $token")
            .addHeader("Accept", "application/json")
            .get()
            .build()
        return execute(request) { json ->
            val arr = json.getJSONArray("branches")
            val list = (0 until arr.length()).map { i ->
                val o = arr.getJSONObject(i)
                RestockBranch(
                    id = o.optString("id", ""),
                    name = o.optString("name", ""),
                    code = o.optString("code", "")
                )
            }
            RestockBranchesResponse(branches = list)
        }
    }

    fun getRestockProducts(token: String): ApiResult<RestockProductsResponse> {
        val request = Request.Builder()
            .url("${baseUrl()}/api/restock/products")
            .addHeader("Authorization", "Bearer $token")
            .addHeader("Accept", "application/json")
            .get()
            .build()
        return execute(request) { json ->
            val arr = json.getJSONArray("products")
            val list = (0 until arr.length()).map { i ->
                val o = arr.getJSONObject(i)
                RestockProduct(
                    id = o.optString("id", ""),
                    name = sequenceOf("name", "product_name", "package_name")
                        .map { o.optString(it, "") }
                        .firstOrNull { it.isNotEmpty() } ?: "",
                    sku = o.optString("sku", "")
                )
            }
            RestockProductsResponse(products = list)
        }
    }

    fun createRestockOrder(
        token: String,
        branchId: String?,
        referenceNumber: String?,
        dealershipName: String?,
        expectedAt: String?,
        productIds: List<String>,
        quantities: List<Int>,
        costs: List<Double?>
    ): ApiResult<RestockOrderCreatedResponse> {
        val body = JSONObject().apply {
            if (!branchId.isNullOrEmpty()) put("branch_id", branchId)
            if (!referenceNumber.isNullOrEmpty()) put("reference_number", referenceNumber)
            if (!dealershipName.isNullOrEmpty()) put("dealership_name", dealershipName)
            if (!expectedAt.isNullOrEmpty()) put("expected_at", expectedAt)
            if (productIds.size == 1) {
                put("product_id", productIds[0])
                put("quantity", quantities[0])
                costs[0]?.let { put("total_acquisition_cost", it) }
            } else {
                put("product_id", org.json.JSONArray(productIds))
                put("quantity", org.json.JSONArray(quantities))
                put("total_acquisition_cost", org.json.JSONArray().apply {
                costs.forEachIndexed { i, c -> put(i, c ?: org.json.JSONObject.NULL) }
            })
            }
        }.toString().toRequestBody(jsonType)
        val request = Request.Builder()
            .url("${baseUrl()}/api/restock/orders")
            .addHeader("Authorization", "Bearer $token")
            .addHeader("Accept", "application/json")
            .addHeader("Content-Type", "application/json")
            .post(body)
            .build()
        return execute(request) { json ->
            val orderObj = json.optJSONObject("order")
            val ordersArr = json.optJSONArray("orders")
            RestockOrderCreatedResponse(
                message = json.optString("message", ""),
                order_batch = json.optString("order_batch", null).takeIf { !it.isNullOrEmpty() },
                order = orderObj?.let { o ->
                    RestockOrderItem(
                        id = o.optString("id", ""),
                        order_number = o.optString("order_number", ""),
                        product_id = o.optString("product_id", ""),
                        product_name = o.optString("product_name", null).takeIf { !it.isNullOrEmpty() },
                        quantity_ordered = o.optInt("quantity_ordered", 0)
                    )
                },
                orders = if (ordersArr != null) (0 until ordersArr.length()).map { i ->
                    val o = ordersArr.getJSONObject(i)
                    RestockOrderItem(
                        id = o.optString("id", ""),
                        order_number = o.optString("order_number", ""),
                        product_id = o.optString("product_id", ""),
                        product_name = o.optString("product_name", null).takeIf { !it.isNullOrEmpty() },
                        quantity_ordered = o.optInt("quantity_ordered", 0)
                    )
                } else null
            )
        }
    }

    fun getRestockOrdersPending(token: String): ApiResult<PendingRestockOrdersResponse> {
        val request = Request.Builder()
            .url("${baseUrl()}/api/restock/orders")
            .addHeader("Authorization", "Bearer $token")
            .addHeader("Accept", "application/json")
            .get()
            .build()
        return execute(request) { json ->
            val arr = json.getJSONArray("orders")
            val list = (0 until arr.length()).map { i ->
                val o = arr.getJSONObject(i)
                PendingRestockOrder(
                    id = o.optString("id", ""),
                    order_number = o.optString("order_number", ""),
                    order_batch = o.optString("order_batch", null).takeIf { !it.isNullOrEmpty() },
                    branch_id = o.optString("branch_id", ""),
                    branch_name = o.optString("branch_name", null).takeIf { !it.isNullOrEmpty() },
                    branch_code = o.optString("branch_code", null).takeIf { !it.isNullOrEmpty() },
                    product_id = o.optString("product_id", ""),
                    product_name = o.optString("product_name", null).takeIf { !it.isNullOrEmpty() },
                    product_sku = o.optString("product_sku", null).takeIf { !it.isNullOrEmpty() },
                    quantity_ordered = o.optInt("quantity_ordered", 0),
                    quantity_received = o.optInt("quantity_received", 0),
                    quantity_outstanding = o.optInt("quantity_outstanding", 0),
                    reference_number = o.optString("reference_number", null).takeIf { !it.isNullOrEmpty() },
                    dealership_name = o.optString("dealership_name", null).takeIf { !it.isNullOrEmpty() },
                    expected_at = o.optString("expected_at", null).takeIf { !it.isNullOrEmpty() },
                    ordered_at = o.optString("ordered_at", null).takeIf { !it.isNullOrEmpty() },
                    created_by_name = o.optString("created_by_name", null).takeIf { !it.isNullOrEmpty() }
                )
            }
            PendingRestockOrdersResponse(orders = list)
        }
    }

    fun receiveRestockOrder(
        token: String,
        orderId: String,
        quantityReceived: Int,
        markOrderComplete: Boolean,
        notes: String? = null,
        imeis: String? = null
    ): ApiResult<String> {
        val body = org.json.JSONObject().apply {
            put("quantity_received", quantityReceived)
            put("mark_order_complete", markOrderComplete)
            if (!notes.isNullOrBlank()) put("notes", notes.trim())
            if (!imeis.isNullOrBlank()) put("imeis", imeis.trim())
        }.toString().toRequestBody(jsonType)
        val request = Request.Builder()
            .url("${baseUrl()}/api/restock/orders/$orderId/receive")
            .addHeader("Authorization", "Bearer $token")
            .addHeader("Accept", "application/json")
            .addHeader("Content-Type", "application/json")
            .post(body)
            .build()
        return execute(request) { json ->
            json.optString("message", "Stock received.")
        }
    }

    fun approveRestockOrder(token: String, orderId: String, imeis: String? = null): ApiResult<String> {
        val body = org.json.JSONObject().apply {
            if (!imeis.isNullOrBlank()) put("imeis", imeis.trim())
        }.toString().toRequestBody(jsonType)
        val request = Request.Builder()
            .url("${baseUrl()}/api/restock/orders/$orderId/approve")
            .addHeader("Authorization", "Bearer $token")
            .addHeader("Accept", "application/json")
            .addHeader("Content-Type", "application/json")
            .post(body)
            .build()
        return execute(request) { json ->
            json.optString("message", "Order approved.")
        }
    }

    fun markRestockOrderComplete(token: String, orderId: String): ApiResult<String> {
        val body = org.json.JSONObject().toString().toRequestBody(jsonType)
        val request = Request.Builder()
            .url("${baseUrl()}/api/restock/orders/$orderId/mark-complete")
            .addHeader("Authorization", "Bearer $token")
            .addHeader("Accept", "application/json")
            .addHeader("Content-Type", "application/json")
            .post(body)
            .build()
        return execute(request) { json ->
            json.optString("message", "Order marked complete.")
        }
    }

    fun updateRestockOrderQuantity(
        token: String,
        orderId: String,
        quantityOrdered: Int,
        password: String
    ): ApiResult<String> {
        val body = org.json.JSONObject().apply {
            put("quantity_ordered", quantityOrdered)
            put("password", password)
        }.toString().toRequestBody(jsonType)
        val request = Request.Builder()
            .url("${baseUrl()}/api/restock/orders/$orderId/quantity")
            .addHeader("Authorization", "Bearer $token")
            .addHeader("Accept", "application/json")
            .addHeader("Content-Type", "application/json")
            .put(body)
            .build()
        return execute(request) { json ->
            json.optString("message", "Quantity updated.")
        }
    }

    fun rejectRestockOrder(token: String, orderId: String, reason: String?): ApiResult<String> {
        val body = org.json.JSONObject().apply {
            if (!reason.isNullOrEmpty()) put("rejection_reason", reason)
        }.toString().toRequestBody(jsonType)
        val request = Request.Builder()
            .url("${baseUrl()}/api/restock/orders/$orderId/reject")
            .addHeader("Authorization", "Bearer $token")
            .addHeader("Accept", "application/json")
            .addHeader("Content-Type", "application/json")
            .post(body)
            .build()
        return execute(request) { json ->
            json.optString("message", "Order rejected.")
        }
    }

    // --- Stock take ---
    data class StockTakeListItem(
        val id: String,
        val stock_take_number: String,
        val branch_id: String,
        val branch_name: String?,
        val branch_code: String?,
        val status: String,
        val stock_take_date: String?,
        val notes: String?,
        val created_by_name: String?,
        val created_at: String?,
        val items_count: Int,
        val counted_count: Int
    )
    data class StockTakeListResponse(val stock_takes: List<StockTakeListItem>)
    data class StockTakeProductData(val id: String, val name: String, val sku: String, val current_stock: Int)
    data class StockTakeCreateDataResponse(
        val branches: List<RestockBranch>,
        val products: List<StockTakeProductData>
    )
    data class StockTakeItemData(
        val id: String,
        val product_id: String,
        val product_name: String?,
        val product_sku: String?,
        val system_quantity: Int,
        val physical_quantity: Int?,
        val variance: Int,
        val notes: String?,
        val submitted_imeis: List<String>?
    )
    data class StockTakeFull(
        val id: String,
        val stock_take_number: String,
        val branch_id: String,
        val branch_name: String?,
        val branch_code: String?,
        val status: String,
        val stock_take_date: String?,
        val notes: String?,
        val created_by_name: String?,
        val created_at: String?,
        val items: List<StockTakeItemData>
    )
    data class StockTakeSummary(
        val total_items: Int,
        val counted_items: Int,
        val pending_items: Int,
        val items_with_variance: Int,
        val total_variance: Int,
        val overstock_count: Int,
        val shortage_count: Int
    )
    data class StockTakeShowResponse(val stock_take: StockTakeFull, val summary: StockTakeSummary)
    data class StockTakeEditDataResponse(
        val branch_stocks: List<StockTakeBranchStockRow>,
        val existing_product_ids: List<String>
    )
    data class StockTakeBranchStockRow(
        val product_id: String,
        val product_name: String?,
        val product_sku: String?,
        val quantity: Int
    )

    fun getStockTakes(token: String, status: String? = null): ApiResult<StockTakeListResponse> {
        val url = if (!status.isNullOrEmpty()) "${baseUrl()}/api/stock-takes?status=$status" else "${baseUrl()}/api/stock-takes"
        val request = Request.Builder()
            .url(url)
            .addHeader("Authorization", "Bearer $token")
            .addHeader("Accept", "application/json")
            .get()
            .build()
        return execute(request) { json ->
            val arr = json.getJSONArray("stock_takes")
            val list = (0 until arr.length()).map { i ->
                val o = arr.getJSONObject(i)
                StockTakeListItem(
                    id = o.optString("id", ""),
                    stock_take_number = o.optString("stock_take_number", ""),
                    branch_id = o.optString("branch_id", ""),
                    branch_name = o.optString("branch_name", null).takeIf { !it.isNullOrEmpty() },
                    branch_code = o.optString("branch_code", null).takeIf { !it.isNullOrEmpty() },
                    status = o.optString("status", ""),
                    stock_take_date = o.optString("stock_take_date", null).takeIf { !it.isNullOrEmpty() },
                    notes = o.optString("notes", null).takeIf { !it.isNullOrEmpty() },
                    created_by_name = o.optString("created_by_name", null).takeIf { !it.isNullOrEmpty() },
                    created_at = o.optString("created_at", null).takeIf { !it.isNullOrEmpty() },
                    items_count = o.optInt("items_count", 0),
                    counted_count = o.optInt("counted_count", 0)
                )
            }
            StockTakeListResponse(stock_takes = list)
        }
    }

    fun getStockTakeCreateData(token: String): ApiResult<StockTakeCreateDataResponse> {
        val request = Request.Builder()
            .url("${baseUrl()}/api/stock-takes/create-data")
            .addHeader("Authorization", "Bearer $token")
            .addHeader("Accept", "application/json")
            .get()
            .build()
        return execute(request) { json ->
            val branchesArr = json.getJSONArray("branches")
            val branches = (0 until branchesArr.length()).map { i ->
                val o = branchesArr.getJSONObject(i)
                RestockBranch(id = o.optString("id", ""), name = o.optString("name", ""), code = o.optString("code", ""))
            }
            val productsArr = json.getJSONArray("products")
            val products = (0 until productsArr.length()).map { i ->
                val o = productsArr.getJSONObject(i)
                StockTakeProductData(
                    id = o.optString("id", ""),
                    name = o.optString("name", ""),
                    sku = o.optString("sku", ""),
                    current_stock = o.optInt("current_stock", 0)
                )
            }
            StockTakeCreateDataResponse(branches = branches, products = products)
        }
    }

    fun createStockTake(
        token: String,
        branchId: String,
        stockTakeDate: String,
        notes: String?,
        items: List<Pair<String, Int>>?
    ): ApiResult<Pair<String, StockTakeFull>> {
        val body = org.json.JSONObject().apply {
            put("branch_id", branchId)
            put("stock_take_date", stockTakeDate)
            if (!notes.isNullOrBlank()) put("notes", notes.trim())
            items?.takeIf { it.isNotEmpty() }?.let { list ->
                val arr = org.json.JSONArray()
                list.forEach { (productId, openingStock) ->
                    arr.put(org.json.JSONObject().apply {
                        put("product_id", productId)
                        put("opening_stock", openingStock)
                    })
                }
                put("items", arr)
            }
        }.toString().toRequestBody(jsonType)
        val request = Request.Builder()
            .url("${baseUrl()}/api/stock-takes")
            .addHeader("Authorization", "Bearer $token")
            .addHeader("Accept", "application/json")
            .addHeader("Content-Type", "application/json")
            .post(body)
            .build()
        return execute(request) { json ->
            val st = json.getJSONObject("stock_take")
            Pair(json.optString("message", ""), parseStockTakeFull(st))
        }
    }

    fun getStockTake(token: String, stockTakeId: String): ApiResult<StockTakeShowResponse> {
        val request = Request.Builder()
            .url("${baseUrl()}/api/stock-takes/$stockTakeId")
            .addHeader("Authorization", "Bearer $token")
            .addHeader("Accept", "application/json")
            .get()
            .build()
        return execute(request) { json ->
            val st = json.getJSONObject("stock_take")
            val summaryObj = json.getJSONObject("summary")
            StockTakeShowResponse(
                stock_take = parseStockTakeFull(st),
                summary = StockTakeSummary(
                    total_items = summaryObj.optInt("total_items", 0),
                    counted_items = summaryObj.optInt("counted_items", 0),
                    pending_items = summaryObj.optInt("pending_items", 0),
                    items_with_variance = summaryObj.optInt("items_with_variance", 0),
                    total_variance = summaryObj.optInt("total_variance", 0),
                    overstock_count = summaryObj.optInt("overstock_count", 0),
                    shortage_count = summaryObj.optInt("shortage_count", 0)
                )
            )
        }
    }

    /** API may return id as number or string; normalize to string. */
    private fun optIdString(o: org.json.JSONObject, key: String): String {
        val v = o.opt(key) ?: return ""
        return if (v is Number) v.toString() else o.optString(key, "")
    }

    private fun parseStockTakeFull(st: org.json.JSONObject): StockTakeFull {
        val itemsArr = st.optJSONArray("items") ?: org.json.JSONArray()
        val items = (0 until itemsArr.length()).map { i ->
            val o = itemsArr.getJSONObject(i)
            val imeisArr = o.optJSONArray("submitted_imeis")
            val imeis = if (imeisArr != null) (0 until imeisArr.length()).map { j -> imeisArr.optString(j, "") }.filter { it.isNotEmpty() } else null
            StockTakeItemData(
                id = optIdString(o, "id"),
                product_id = optIdString(o, "product_id"),
                product_name = o.optString("product_name", null).takeIf { !it.isNullOrEmpty() },
                product_sku = o.optString("product_sku", null).takeIf { !it.isNullOrEmpty() },
                system_quantity = o.optInt("system_quantity", 0),
                physical_quantity = if (o.has("physical_quantity") && !o.isNull("physical_quantity")) o.optInt("physical_quantity", 0) else null,
                variance = o.optInt("variance", 0),
                notes = o.optString("notes", null).takeIf { !it.isNullOrEmpty() },
                submitted_imeis = imeis
            )
        }
        return StockTakeFull(
            id = st.optString("id", ""),
            stock_take_number = st.optString("stock_take_number", ""),
            branch_id = st.optString("branch_id", ""),
            branch_name = st.optString("branch_name", null).takeIf { !it.isNullOrEmpty() },
            branch_code = st.optString("branch_code", null).takeIf { !it.isNullOrEmpty() },
            status = st.optString("status", ""),
            stock_take_date = st.optString("stock_take_date", null).takeIf { !it.isNullOrEmpty() },
            notes = st.optString("notes", null).takeIf { !it.isNullOrEmpty() },
            created_by_name = st.optString("created_by_name", null).takeIf { !it.isNullOrEmpty() },
            created_at = st.optString("created_at", null).takeIf { !it.isNullOrEmpty() },
            items = items
        )
    }

    fun getStockTakeEditData(token: String, stockTakeId: String): ApiResult<StockTakeEditDataResponse> {
        val request = Request.Builder()
            .url("${baseUrl()}/api/stock-takes/$stockTakeId/edit-data")
            .addHeader("Authorization", "Bearer $token")
            .addHeader("Accept", "application/json")
            .get()
            .build()
        return execute(request) { json ->
            val arr = json.getJSONArray("branch_stocks")
            val branch_stocks = (0 until arr.length()).map { i ->
                val o = arr.getJSONObject(i)
                StockTakeBranchStockRow(
                    product_id = o.optString("product_id", ""),
                    product_name = o.optString("product_name", null).takeIf { !it.isNullOrEmpty() },
                    product_sku = o.optString("product_sku", null).takeIf { !it.isNullOrEmpty() },
                    quantity = o.optInt("quantity", 0)
                )
            }
            val idsArr = json.getJSONArray("existing_product_ids")
            val existing_product_ids = (0 until idsArr.length()).map { i -> idsArr.getString(i) }
            StockTakeEditDataResponse(branch_stocks = branch_stocks, existing_product_ids = existing_product_ids)
        }
    }

    fun updateStockTake(token: String, stockTakeId: String, stockTakeDate: String, notes: String?): ApiResult<String> {
        val body = org.json.JSONObject().apply {
            put("stock_take_date", stockTakeDate)
            if (!notes.isNullOrBlank()) put("notes", notes.trim())
        }.toString().toRequestBody(jsonType)
        val request = Request.Builder()
            .url("${baseUrl()}/api/stock-takes/$stockTakeId")
            .addHeader("Authorization", "Bearer $token")
            .addHeader("Accept", "application/json")
            .addHeader("Content-Type", "application/json")
            .put(body)
            .build()
        return execute(request) { json -> json.optString("message", "Updated.") }
    }

    fun addStockTakeItem(token: String, stockTakeId: String, productId: String): ApiResult<StockTakeItemData> {
        val body = org.json.JSONObject().apply { put("product_id", productId) }.toString().toRequestBody(jsonType)
        val request = Request.Builder()
            .url("${baseUrl()}/api/stock-takes/$stockTakeId/add-item")
            .addHeader("Authorization", "Bearer $token")
            .addHeader("Accept", "application/json")
            .addHeader("Content-Type", "application/json")
            .post(body)
            .build()
        return execute(request) { json ->
            val o = json.getJSONObject("item")
            val imeisArr = o.optJSONArray("submitted_imeis")
            val imeis = if (imeisArr != null) (0 until imeisArr.length()).map { j -> imeisArr.optString(j, "") }.filter { it.isNotEmpty() } else null
            StockTakeItemData(
                id = optIdString(o, "id"),
                product_id = optIdString(o, "product_id"),
                product_name = o.optString("product_name", null).takeIf { !it.isNullOrEmpty() },
                product_sku = o.optString("product_sku", null).takeIf { !it.isNullOrEmpty() },
                system_quantity = o.optInt("system_quantity", 0),
                physical_quantity = if (o.has("physical_quantity") && !o.isNull("physical_quantity")) o.optInt("physical_quantity", 0) else null,
                variance = o.optInt("variance", 0),
                notes = o.optString("notes", null).takeIf { !it.isNullOrEmpty() },
                submitted_imeis = imeis
            )
        }
    }

    fun updateStockTakeItem(
        token: String,
        stockTakeId: String,
        itemId: String,
        physicalQuantity: Int,
        notes: String? = null,
        imeis: String? = null
    ): ApiResult<String> {
        val body = org.json.JSONObject().apply {
            put("physical_quantity", physicalQuantity)
            if (!notes.isNullOrBlank()) put("notes", notes.trim())
            if (!imeis.isNullOrBlank()) put("imeis", imeis!!.trim())
        }.toString().toRequestBody(jsonType)
        val request = Request.Builder()
            .url("${baseUrl()}/api/stock-takes/$stockTakeId/items/$itemId")
            .addHeader("Authorization", "Bearer $token")
            .addHeader("Accept", "application/json")
            .addHeader("Content-Type", "application/json")
            .put(body)
            .build()
        return execute(request) { json -> json.optString("message", "Count updated.") }
    }

    fun removeStockTakeItem(token: String, stockTakeId: String, itemId: String): ApiResult<String> {
        val request = Request.Builder()
            .url("${baseUrl()}/api/stock-takes/$stockTakeId/items/$itemId")
            .addHeader("Authorization", "Bearer $token")
            .addHeader("Accept", "application/json")
            .delete()
            .build()
        return execute(request) { json -> json.optString("message", "Item removed.") }
    }

    fun completeStockTake(
        token: String,
        stockTakeId: String,
        items: List<Triple<String, Int, String?>>?
    ): ApiResult<String> {
        val body = org.json.JSONObject().apply {
            items?.takeIf { it.isNotEmpty() }?.let { list ->
                val arr = org.json.JSONArray()
                list.forEach { (itemId, physicalQty, imeisStr) ->
                    arr.put(org.json.JSONObject().apply {
                        put("item_id", itemId)
                        put("physical_quantity", physicalQty)
                        if (!imeisStr.isNullOrBlank()) put("imeis", imeisStr!!.trim())
                    })
                }
                put("items", arr)
            }
        }.toString().toRequestBody(jsonType)
        val request = Request.Builder()
            .url("${baseUrl()}/api/stock-takes/$stockTakeId/complete")
            .addHeader("Authorization", "Bearer $token")
            .addHeader("Accept", "application/json")
            .addHeader("Content-Type", "application/json")
            .post(body)
            .build()
        return execute(request) { json -> json.optString("message", "Completed.") }
    }

    fun approveStockTake(token: String, stockTakeId: String, approvalNotes: String? = null): ApiResult<String> {
        val body = org.json.JSONObject().apply {
            if (!approvalNotes.isNullOrBlank()) put("approval_notes", approvalNotes!!.trim())
        }.toString().toRequestBody(jsonType)
        val request = Request.Builder()
            .url("${baseUrl()}/api/stock-takes/$stockTakeId/approve")
            .addHeader("Authorization", "Bearer $token")
            .addHeader("Accept", "application/json")
            .addHeader("Content-Type", "application/json")
            .post(body)
            .build()
        return execute(request) { json -> json.optString("message", "Approved.") }
    }

    fun cancelStockTake(token: String, stockTakeId: String, reason: String? = null): ApiResult<String> {
        val body = org.json.JSONObject().apply {
            if (!reason.isNullOrBlank()) put("cancellation_reason", reason!!.trim())
        }.toString().toRequestBody(jsonType)
        val request = Request.Builder()
            .url("${baseUrl()}/api/stock-takes/$stockTakeId/cancel")
            .addHeader("Authorization", "Bearer $token")
            .addHeader("Accept", "application/json")
            .addHeader("Content-Type", "application/json")
            .post(body)
            .build()
        return execute(request) { json -> json.optString("message", "Cancelled.") }
    }
}
