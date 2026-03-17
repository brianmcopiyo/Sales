package com.tajacore.distribution.ui.checkin

import android.Manifest
import android.content.pm.PackageManager
import android.location.Location
import android.net.Uri
import android.os.Bundle
import android.widget.Toast
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.getValue
import androidx.compose.runtime.setValue
import androidx.core.content.FileProvider
import androidx.lifecycle.lifecycleScope
import com.tajacore.distribution.data.AuthRepository
import com.tajacore.distribution.data.api.RetrofitModule
import com.tajacore.distribution.data.local.PendingCheckIn
import com.tajacore.distribution.data.local.createAppDatabase
import com.tajacore.distribution.ui.theme.TajaCoreDistributionTheme
import com.tajacore.distribution.worker.SyncWorker
import com.google.android.gms.location.FusedLocationProviderClient
import com.google.android.gms.location.LocationServices
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody
import okhttp3.RequestBody.Companion.asRequestBody
import okhttp3.RequestBody.Companion.toRequestBody
import java.io.File
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale
import java.util.UUID
import androidx.activity.result.contract.ActivityResultContracts
import androidx.work.Constraints
import androidx.work.ExistingWorkPolicy
import androidx.work.NetworkType
import androidx.work.OneTimeWorkRequestBuilder
import androidx.work.WorkManager
import androidx.core.content.ContextCompat

class CheckInActivity : ComponentActivity() {

    companion object {
        const val EXTRA_OUTLET_ID = "outlet_id"
        const val EXTRA_OUTLET_NAME = "outlet_name"
    }

    private lateinit var fusedLocation: FusedLocationProviderClient

    private var locationText by mutableStateOf("")
    private var photoPath by mutableStateOf<String?>(null)
    private var currentLat: Double? = null
    private var currentLng: Double? = null
    private var notes by mutableStateOf("")
    private var loading by mutableStateOf(false)

    private val takePicture = registerForActivityResult(ActivityResultContracts.TakePicture()) { success ->
        if (success && photoPath != null) { /* state already has photoPath */ }
    }

    private val requestPermission = registerForActivityResult(ActivityResultContracts.RequestMultiplePermissions()) { map ->
        if (map[Manifest.permission.ACCESS_FINE_LOCATION] == true) getLocation()
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        fusedLocation = LocationServices.getFusedLocationProviderClient(this)

        val outletName = intent.getStringExtra(EXTRA_OUTLET_NAME) ?: ""

        setContent {
            TajaCoreDistributionTheme {
                CheckInScreen(
                    outletName = outletName,
                    locationText = locationText,
                    photoCaptured = photoPath != null,
                    notes = notes,
                    onNotesChange = { notes = it },
                    loading = loading,
                    onGetLocation = { getLocation() },
                    onTakePhoto = { capturePhoto() },
                    onSubmit = { submit() }
                )
            }
        }
    }

    private fun getLocation() {
        if (ContextCompat.checkSelfPermission(this, Manifest.permission.ACCESS_FINE_LOCATION) != PackageManager.PERMISSION_GRANTED) {
            requestPermission.launch(arrayOf(Manifest.permission.ACCESS_FINE_LOCATION, Manifest.permission.ACCESS_COARSE_LOCATION))
            return
        }
        fusedLocation.lastLocation.addOnSuccessListener { location: Location? ->
            if (location != null) {
                currentLat = location.latitude
                currentLng = location.longitude
                locationText = "%.5f, %.5f".format(location.latitude, location.longitude)
            } else {
                Toast.makeText(this, "Location not available", Toast.LENGTH_SHORT).show()
            }
        }
    }

    private fun capturePhoto() {
        val file = File(cacheDir, "checkin_${System.currentTimeMillis()}.jpg")
        photoPath = file.absolutePath
        val uri = FileProvider.getUriForFile(this, "$packageName.fileprovider", file)
        takePicture.launch(uri)
    }

    private fun submit() {
        val outletId = intent.getStringExtra(EXTRA_OUTLET_ID) ?: return
        val outletName = intent.getStringExtra(EXTRA_OUTLET_NAME) ?: ""
        val lat = currentLat
        val lng = currentLng
        if (lat == null || lng == null) {
            Toast.makeText(this, "Get location first", Toast.LENGTH_SHORT).show()
            return
        }
        loading = true

        lifecycleScope.launch {
            val authRepo = AuthRepository(this@CheckInActivity)
            val token = authRepo.getToken() ?: run {
                withContext(Dispatchers.Main) {
                    Toast.makeText(this@CheckInActivity, "Not logged in", Toast.LENGTH_SHORT).show()
                    loading = false
                }
                return@launch
            }
            val baseUrl = authRepo.getBaseUrl()
            val api = RetrofitModule.apiService(baseUrl)
            val checkInAt = SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss'Z'", Locale.US).apply { timeZone = java.util.TimeZone.getTimeZone("UTC") }.format(Date())
            val notesValue = notes.trim().ifEmpty { null }

            val success = withContext(Dispatchers.IO) {
                try {
                    val path = photoPath
                    if (path != null) {
                        val file = File(path)
                        if (file.exists()) {
                            val part = MultipartBody.Part.createFormData("photo", file.name, file.asRequestBody("image/jpeg".toMediaTypeOrNull()))
                            val response = api.submitCheckIn(
                                "Bearer $token",
                                outletId.toRequestBody(),
                                lat.toString().toRequestBody(),
                                lng.toString().toRequestBody(),
                                (notesValue ?: "").toRequestBody(),
                                part
                            )
                            response.isSuccessful
                        } else {
                            val response = api.submitCheckIn(
                                "Bearer $token",
                                outletId.toRequestBody(),
                                lat.toString().toRequestBody(),
                                lng.toString().toRequestBody(),
                                (notesValue ?: "").toRequestBody(),
                                null
                            )
                            response.isSuccessful
                        }
                    } else {
                        val response = api.submitCheckIn(
                            "Bearer $token",
                            outletId.toRequestBody(),
                            lat.toString().toRequestBody(),
                            lng.toString().toRequestBody(),
                            (notesValue ?: "").toRequestBody(),
                            null
                        )
                        response.isSuccessful
                    }
                } catch (e: Exception) {
                    false
                }
            }

            withContext(Dispatchers.Main) {
                loading = false
                if (success) {
                    Toast.makeText(this@CheckInActivity, "Check-in recorded", Toast.LENGTH_SHORT).show()
                    finish()
                } else {
                    val clientId = UUID.randomUUID().toString()
                    val db = createAppDatabase(this@CheckInActivity)
                    val localPath = photoPath?.let { path ->
                        val f = File(path)
                        if (f.exists()) {
                            val dest = File(filesDir, "pending_${clientId}.jpg")
                            f.copyTo(dest, overwrite = true)
                            dest.name
                        } else null
                    }
                    db.pendingCheckInDao().insert(
                        PendingCheckIn(
                            clientId = clientId,
                            outletId = outletId,
                            outletName = outletName,
                            lat = lat,
                            lng = lng,
                            notes = notesValue,
                            photoPath = localPath,
                            checkInAt = checkInAt
                        )
                    )
                    WorkManager.getInstance(this@CheckInActivity).enqueueUniqueWork(
                        "sync_check_ins",
                        ExistingWorkPolicy.REPLACE,
                        OneTimeWorkRequestBuilder<SyncWorker>().setConstraints(
                            Constraints.Builder().setRequiredNetworkType(NetworkType.CONNECTED).build()
                        ).build()
                    )
                    Toast.makeText(this@CheckInActivity, "Saved offline. Will sync when online.", Toast.LENGTH_LONG).show()
                    finish()
                }
            }
        }
    }

    private fun String.toRequestBody(): RequestBody = RequestBody.create("text/plain".toMediaTypeOrNull(), this)
}
