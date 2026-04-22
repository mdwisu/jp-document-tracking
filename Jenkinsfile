pipeline {
    agent any

    environment {
        PHP          = 'D:\\php-8.3.16-nts\\php.exe'
        COMPOSER_BIN = 'C:\\ProgramData\\ComposerSetup\\bin\\composer.phar'
        DEPLOY_DIR   = 'D:\\projects\\jp-document-tracking'
        APP_SITE     = 'jp-document-tracking'
        PATH         = "D:\\php-8.3.16-nts;${env.PATH}"
    }

    stages {

        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Install Dependencies') {
            steps {
                bat "${PHP} ${COMPOSER_BIN} install --no-dev --optimize-autoloader --no-interaction"
            }
        }

        stage('Prepare .env') {
            steps {
                withCredentials([file(credentialsId: 'jp-doc-env', variable: 'ENV_FILE')]) {
                    bat "copy /Y \"${ENV_FILE}\" .env"
                }
            }
        }

        stage('Migrate') {
            steps {
                bat "${PHP} artisan migrate --force"
            }
        }

        stage('Deploy ke IIS') {
            steps {
                // Copy .env production ke deploy dir lebih dulu
                withCredentials([file(credentialsId: 'jp-doc-env', variable: 'ENV_FILE')]) {
                    bat "copy /Y \"${ENV_FILE}\" \"%DEPLOY_DIR%\\.env\""
                }

                // Sync file ke deploy dir
                bat """
                    robocopy . "%DEPLOY_DIR%" /E /XO ^
                        /XD .git .claude vendor\\bin storage\\app\\documents ^
                        /XF .env Jenkinsfile ^
                        /NJH /NJS
                    IF %ERRORLEVEL% LEQ 7 EXIT /B 0
                """
            }
        }

        stage('Optimize di Deploy Dir') {
            steps {
                // Semua perintah artisan dijalankan dari deploy dir agar path cache benar
                bat "cd /d \"%DEPLOY_DIR%\" && ${PHP} artisan config:cache"
                bat "cd /d \"%DEPLOY_DIR%\" && ${PHP} artisan route:cache"
                bat "cd /d \"%DEPLOY_DIR%\" && ${PHP} artisan view:cache"
                bat "if exist \"%DEPLOY_DIR%\\public\\storage\" rmdir /S /Q \"%DEPLOY_DIR%\\public\\storage\""
                bat "cd /d \"%DEPLOY_DIR%\" && ${PHP} artisan storage:link"
            }
        }

        stage('Recycle App Pool') {
            steps {
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
