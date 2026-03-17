package com.tajacore.distribution.worker

import android.content.Context
import android.util.Base64
import androidx.work.CoroutineWorker
import androidx.work.WorkerParameters
import com.tajacore.distribution.data.AuthRepository
import com.tajacore.distribution.data.api.RetrofitModule
import com.tajacore.distribution.data.api.dto.SyncCheckInItem
import com.tajacore.distribution.data.api.dto.SyncRequest
import com.tajacore.distribution.data.local.createAppDatabase
import java.io.File

const val CHANNEL_ID = "distribution_sync"

class SyncWorker(
    context: Context,
    params: WorkerParameters
) : CoroutineWorker(context, params) {

    override suspend fun doWork(): Result = runCatching {
        val authRepo = AuthRepository(applicationContext)
        val token = authRepo.getToken() ?: return Result.failure()
        val baseUrl = authRepo.getBaseUrl()
        val api = RetrofitModule.apiService(baseUrl)
        val db = createAppDatabase(applicationContext)
        val dao = db.pendingCheckInDao()
        val pending = dao.getAll()
        if (pending.isEmpty()) return Result.success()

        val items = pending.map { p ->
            val photoBase64 = p.photoPath?.let { path ->
                File(applicationContext.filesDir, path).takeIf { it.exists() }?.readBytes()?.let {
                    Base64.encodeToString(it, Base64.NO_WRAP)
                }
            }
            SyncCheckInItem(
                clientId = p.clientId,
                outletId = p.outletId,
                lat = p.lat,
                lng = p.lng,
                notes = p.notes,
                photoBase64 = photoBase64,
                checkInAt = p.checkInAt
            )
        }
        val response = api.syncCheckIns("Bearer $token", SyncRequest(items))
        if (!response.isSuccessful) return Result.retry()
        val body = response.body() ?: return Result.success()
        body.synced.forEach { dao.deleteByClientId(it.clientId) }
        Result.success()
    }.getOrElse { Result.failure() }
}
