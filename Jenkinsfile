pipeline {
    agent any

    environment {
        PHP         = 'D:\\php-8.3.16-nts\\php.exe'
        COMPOSER    = 'C:\\ProgramData\\ComposerSetup\\bin\\composer.bat'
        DEPLOY_DIR  = 'D:\\projects\\jp-document-tracking'
        APP_SITE    = 'jp-document-tracking'                        // nama app pool di IIS, sesuaikan jika beda
    }

    stages {

        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Install Dependencies') {
            steps {
                bat "${COMPOSER} install --no-dev --optimize-autoloader --no-interaction"
            }
        }

        stage('Prepare .env') {
            steps {
                // Ambil .env dari Jenkins Credentials (type: Secret file, ID: jp-doc-env)
                withCredentials([file(credentialsId: 'jp-doc-env', variable: 'ENV_FILE')]) {
                    bat "copy /Y \"${ENV_FILE}\" .env"
                }
            }
        }

        stage('Optimize') {
            steps {
                bat "${PHP} artisan config:cache"
                bat "${PHP} artisan route:cache"
                bat "${PHP} artisan view:cache"
            }
        }

        stage('Migrate') {
            steps {
                bat "${PHP} artisan migrate --force"
            }
        }

        stage('Deploy ke IIS') {
            steps {
                // Sync file ke folder IIS (kecuali storage & .env)
                bat """
                    robocopy . "%DEPLOY_DIR%" /E /XO ^
                        /XD .git .claude vendor\\bin storage\\app\\documents ^
                        /XF .env Jenkinsfile ^
                        /NJH /NJS
                    IF %ERRORLEVEL% LEQ 7 EXIT /B 0
                """

                // Pastikan storage di deploy dir punya symlink / folder yang benar
                bat "${PHP} \"%DEPLOY_DIR%\\artisan\" storage:link --force"
            }
        }

        stage('Recycle App Pool') {
            steps {
                // Recycle app pool IIS agar PHP cache fresh
                bat "C:\\Windows\\System32\\inetsrv\\appcmd recycle apppool /apppool.name:\"${APP_SITE}\""
            }
        }
    }

    post {
        success {
            echo "Deploy berhasil ke ${DEPLOY_DIR}"
        }
        failure {
            echo "Deploy GAGAL — cek log di atas"
        }
    }
}
