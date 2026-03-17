package com.tajacore.distribution.ui.navigation

import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.rememberCoroutineScope
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.rememberNavController
import com.tajacore.distribution.BuildConfig
import com.tajacore.distribution.data.AuthRepository
import com.tajacore.distribution.data.api.RetrofitModule
import com.tajacore.distribution.data.api.dto.LoginRequest
import com.tajacore.distribution.data.api.dto.OutletDto
import com.tajacore.distribution.ui.login.LoginScreen
import com.tajacore.distribution.ui.main.MainScreen
import com.tajacore.distribution.ui.theme.TajaCoreDistributionTheme
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext

const val ROUTE_LOGIN = "login"
const val ROUTE_MAIN = "main"

@Composable
fun DistributionNavGraph(
    authRepo: AuthRepository,
    outlets: List<OutletDto>,
    pendingCount: Int,
    loadOutletsAndPending: (onUnauthorized: () -> Unit) -> Unit,
    onOpenCheckIn: (outletId: String, outletName: String) -> Unit
) {
    val navController = rememberNavController()
    val scope = rememberCoroutineScope()

    NavHost(
        navController = navController,
        startDestination = ROUTE_LOGIN
    ) {
        composable(ROUTE_LOGIN) {
            LaunchedEffect(Unit) {
                val token = authRepo.getToken()
                if (token != null) {
                    navController.navigate(ROUTE_MAIN) {
                        popUpTo(ROUTE_LOGIN) { inclusive = true }
                    }
                }
            }
            LoginScreen(
                onLoginSuccess = {
                    navController.navigate(ROUTE_MAIN) {
                        popUpTo(ROUTE_LOGIN) { inclusive = true }
                    }
                },
                onLogin = { login, password, onResult ->
                    scope.launch {
                        try {
                            val api = RetrofitModule.apiService(BuildConfig.API_BASE_URL)
                            val response = api.login(LoginRequest(login, password))
                            if (response.isSuccessful) {
                                response.body()?.let { authRepo.saveToken(it.token) }
                                withContext(Dispatchers.Main) { onResult(null) }
                            } else {
                                val msg = response.errorBody()?.string() ?: "Login failed"
                                withContext(Dispatchers.Main) { onResult(Exception(msg)) }
                            }
                        } catch (e: Exception) {
                            withContext(Dispatchers.Main) { onResult(e) }
                        }
                    }
                }
            )
        }

        composable(ROUTE_MAIN) {
            LaunchedEffect(Unit) {
                loadOutletsAndPending {
                    navController.navigate(ROUTE_LOGIN) {
                        popUpTo(0) { inclusive = true }
                    }
                }
            }
            MainScreen(
                outlets = outlets,
                pendingCount = pendingCount,
                onOutletClick = { outlet -> onOpenCheckIn(outlet.id, outlet.name) },
                onLogout = {
                    scope.launch {
                        authRepo.clearToken()
                        navController.navigate(ROUTE_LOGIN) {
                            popUpTo(0) { inclusive = true }
                        }
                    }
                }
            )
        }
    }
}
