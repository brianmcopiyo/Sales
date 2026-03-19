@echo off
REM Run this to capture the full build output including manifest merger errors.
REM Then open build-output.txt and search for "Error:" or "Manifest merger" to see the exact errors.
echo Building and capturing output to build-output.txt ...
call gradlew.bat :app:processDebugManifest --stacktrace 1> build-output.txt 2>&1
echo Done. Open build-output.txt and search for "Error:" or "Manifest merger"
notepad build-output.txt
