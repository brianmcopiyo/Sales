package com.tajacore.distribution.data.local

import androidx.room.Dao
import androidx.room.Insert
import androidx.room.OnConflictStrategy
import androidx.room.Query

@Dao
interface PendingCheckInDao {

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insert(entity: PendingCheckIn)

    @Query("SELECT * FROM pending_check_ins ORDER BY createdAt ASC")
    suspend fun getAll(): List<PendingCheckIn>

    @Query("DELETE FROM pending_check_ins WHERE clientId = :clientId")
    suspend fun deleteByClientId(clientId: String)

    @Query("DELETE FROM pending_check_ins")
    suspend fun deleteAll()
}
