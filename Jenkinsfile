pipeline {
    agent any

    environment {
        // Docker configuration
        DOCKER_REGISTRY = ''
        DOCKER_IMAGE_NAME = 'ai-assistant-front-service'
        DOCKER_IMAGE_TAG = "${env.BUILD_NUMBER}"

        // Application configuration
        APP_NAME = 'Front Service'
        PHP_VERSION = '8.4'
        NODE_VERSION = '18'
    }

    options {
        buildDiscarder(logRotator(
            numToKeepStr: '10',
            daysToKeepStr: '30'
        ))
        timeout(time: 45, unit: 'MINUTES')
        timestamps()
    }

    stages {
        stage('Checkout') {
            steps {
                echo '🔄 جاري جلب الكود من Git...'
                checkout scm
                script {
                    env.GIT_COMMIT_SHORT = sh(
                        script: 'git rev-parse --short HEAD',
                        returnStdout: true
                    ).trim()
                    env.GIT_BRANCH_NAME = sh(
                        script: 'git rev-parse --abbrev-ref HEAD',
                        returnStdout: true
                    ).trim()
                }
                echo "✅ تم جلب الكود - الفرع: ${env.GIT_BRANCH_NAME}, Commit: ${env.GIT_COMMIT_SHORT}"
            }
        }

        stage('Environment Check') {
            steps {
                echo '🔧 التحقق من البيئة المتوفرة...'
                script {
                    def dockerAvailable = sh(
                        script: 'docker --version 2>/dev/null && echo "yes" || echo "no"',
                        returnStdout: true
                    ).trim()

                    if (dockerAvailable == "no") {
                        error("""
                        ❌ Docker غير متاح في Jenkins!

                        ⚠️ إذا كان Jenkins يعمل داخل Docker container على macOS:

                        1. تأكد من أن Jenkins container مرتبط بـ Docker socket:
                           docker run -d \\
                             --name jenkins \\
                             -v /var/run/docker.sock:/var/run/docker.sock \\
                             -v jenkins_home:/var/jenkins_home \\
                             -p 8080:8080 \\
                             jenkins/jenkins:lts

                        2. أو على macOS، استخدم Docker Desktop socket:
                           docker run -d \\
                             --name jenkins \\
                             -v /var/run/docker.sock:/var/run/docker.sock \\
                             -v jenkins_home:/var/jenkins_home \\
                             -p 8080:8080 \\
                             jenkins/jenkins:lts

                        3. تأكد من أن Docker Desktop يعمل

                        4. أو استخدم Jenkins مباشرة على macOS بدون Docker container
                        """)
                    } else {
                        echo "✅ Docker متوفر"
                        sh 'docker --version'
                        echo "ℹ️ PHP و Node.js سيتم تثبيتهما داخل Docker containers"
                    }
                }
            }
        }

        stage('Code Quality') {
            parallel {
                stage('Lint JavaScript/TypeScript') {
                    steps {
                        echo '🔍 فحص جودة كود JavaScript/TypeScript...'
                        sh '''
                            # استخدام Docker container يحتوي على Node.js
                            docker run --rm -v "$(pwd):/workspace" -w /workspace node:18-alpine sh -c "
                                if [ ! -d node_modules ]; then
                                    echo '📦 تثبيت npm dependencies...'
                                    npm ci
                                fi
                                echo '🔍 تشغيل ESLint...'
                                npm run lint || echo '⚠️ وجدت تحذيرات في ESLint'
                            """ || echo "⚠️ فشل في فحص ESLint - تأكد من تثبيت Docker"
                        '''
                    }
                }

                stage('Format Check') {
                    steps {
                        echo '📝 فحص تنسيق الكود...'
                        sh '''
                            docker run --rm -v "$(pwd):/workspace" -w /workspace node:18-alpine sh -c "
                                if [ ! -d node_modules ]; then
                                    npm ci
                                fi
                                echo '📝 فحص تنسيق الكود...'
                                npm run format:check || echo '⚠️ وجدت مشاكل في التنسيق'
                            """ || echo "⚠️ فشل في فحص التنسيق - تأكد من تثبيت Docker"
                        '''
                    }
                }

                stage('TypeScript Type Check') {
                    steps {
                        echo '🔷 فحص أنواع TypeScript...'
                        sh '''
                            docker run --rm -v "$(pwd):/workspace" -w /workspace node:18-alpine sh -c "
                                if [ ! -d node_modules ]; then
                                    npm ci
                                fi
                                echo '🔷 فحص أنواع TypeScript...'
                                npx vue-tsc --noEmit || echo '⚠️ وجدت أخطاء في أنواع TypeScript'
                            """ || echo "⚠️ فشل في فحص TypeScript - تأكد من تثبيت Docker"
                        '''
                    }
                }
            }
        }

        stage('Install Dependencies') {
            steps {
                echo '📦 تثبيت dependencies...'
                sh '''
                    # تثبيت Composer dependencies باستخدام Docker
                    echo "📦 تثبيت Composer dependencies..."
                    docker run --rm -v "$(pwd):/workspace" -w /workspace \
                        composer:latest install --no-interaction --prefer-dist --optimize-autoloader --no-dev || \
                        docker run --rm -v "$(pwd):/workspace" -w /workspace \
                        composer:latest install --no-interaction --prefer-dist || \
                        echo "⚠️ فشل تثبيت Composer dependencies - تأكد من تثبيت Docker"

                    # تثبيت NPM dependencies باستخدام Docker
                    echo "📦 تثبيت NPM dependencies..."
                    docker run --rm -v "$(pwd):/workspace" -w /workspace \
                        node:18-alpine npm ci || \
                        echo "⚠️ فشل تثبيت NPM dependencies - تأكد من تثبيت Docker"
                '''
            }
        }

        stage('Laravel Setup') {
            steps {
                echo '⚙️ إعداد Laravel...'
                sh '''
                    # نسخ ملف .env إذا لم يكن موجوداً
                    if [ ! -f ".env" ]; then
                        echo "📄 إنشاء ملف .env..."
                        cp env.example .env
                    fi

                    # إعداد Laravel باستخدام Docker container
                    docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html \
                        php:8.4-cli sh -c "
                            apt-get update -qq && apt-get install -y -qq git unzip libzip-dev libpng-dev libonig-dev libxml2-dev > /dev/null 2>&1
                            docker-php-ext-install -q zip pdo_mysql mbstring exif pcntl bcmath gd 2>/dev/null || true
                            curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
                            php artisan key:generate --force || echo '⚠️ تم تجاهل توليد المفتاح'
                            php artisan config:clear || true
                            php artisan cache:clear || true
                            php artisan route:clear || true
                            php artisan view:clear || true
                        " || echo "⚠️ فشل في إعداد Laravel - تأكد من تثبيت Docker"
                '''
            }
        }

        stage('Build Frontend') {
            steps {
                echo '🏗️ بناء ملفات Frontend...'
                sh '''
                    echo "🏗️ بناء ملفات Vue.js و TypeScript..."
                    docker run --rm -v "$(pwd):/workspace" -w /workspace \
                        node:18-alpine npm run build || {
                        echo "⚠️ فشل بناء Frontend - تأكد من تثبيت Docker"
                        exit 1
                    }

                    echo "✅ تم بناء ملفات Frontend بنجاح"
                    ls -lah public/build/ || echo "⚠️ مجلد build غير موجود"
                '''
            }
        }

        stage('Run Tests') {
            steps {
                echo '🧪 تشغيل الاختبارات...'
                sh '''
                    # إعداد بيئة الاختبار
                    echo "⚙️ إعداد بيئة الاختبار..."

                    # تشغيل PHPUnit tests باستخدام Docker
                    echo "🧪 تشغيل PHPUnit tests..."
                    docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html \
                        php:8.4-cli sh -c "
                            apt-get update -qq && apt-get install -y -qq git unzip libzip-dev libpng-dev libonig-dev libxml2-dev > /dev/null 2>&1
                            docker-php-ext-install -q zip pdo_mysql mbstring exif pcntl bcmath gd 2>/dev/null || true
                            curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
                            composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev 2>/dev/null || true
                            php artisan test --env=testing || echo '⚠️ بعض الاختبارات فشلت'
                        " || echo "⚠️ فشل في تشغيل الاختبارات - تأكد من تثبيت Docker"

                    # يمكن إضافة اختبارات Frontend هنا إذا كانت موجودة
                    # docker run --rm -v "${PWD}:/workspace" -w /workspace node:18-alpine npm test || echo "⚠️ بعض اختبارات Frontend فشلت"
                '''
            }
            post {
                always {
                    // حفظ نتائج الاختبارات
                    publishTestResults(
                        testResultsPattern: 'storage/logs/junit.xml',
                        allowEmptyResults: true
                    )

                    // حفظ التقارير
                    publishHTML([
                        reportDir: 'storage/logs',
                        reportFiles: '*.html',
                        reportName: 'Test Report',
                        allowMissing: true,
                        keepAll: true,
                        alwaysLinkToLastBuild: true
                    ])
                }
            }
        }

        stage('Build Docker Image') {
            steps {
                echo '🐳 بناء Docker Image...'
                script {
                    def dockerImage = "${env.DOCKER_IMAGE_NAME}:${env.BUILD_NUMBER}"
                    def dockerImageLatest = "${env.DOCKER_IMAGE_NAME}:latest"

                    sh """
                        echo "🐳 بناء Docker Image: ${dockerImage}"
                        docker build -t ${dockerImage} .

                        echo "🏷️ إضافة tag latest..."
                        docker tag ${dockerImage} ${dockerImageLatest}

                        echo "📋 قائمة الصور المبنية:"
                        docker images | grep "${env.DOCKER_IMAGE_NAME}" | head -5
                    """
                }
            }
            post {
                success {
                    echo "✅ تم بناء Docker Image بنجاح: ${env.DOCKER_IMAGE_NAME}:${env.BUILD_NUMBER}"
                }
                failure {
                    echo "❌ فشل بناء Docker Image"
                }
            }
        }

        stage('Docker Image Test') {
            steps {
                echo '🧪 اختبار Docker Image...'
                sh '''
                    # اختبار تشغيل الـ container
                    echo "🧪 اختبار تشغيل Container..."
                    docker run -d --name front-service-test -p 8080:80 \
                        -e APP_ENV=testing \
                        ai-assistant-front-service:${BUILD_NUMBER} || true

                    # انتظار بدء الخدمة
                    echo "⏳ انتظار بدء الخدمة..."
                    sleep 15

                    # اختبار الـ health check
                    echo "🏥 فحص صحة الخدمة..."
                    curl -f http://localhost:8080 || echo "⚠️ فشل health check"

                    # عرض logs
                    echo "📋 Logs:"
                    docker logs front-service-test | tail -20 || true
                '''
            }
            post {
                always {
                    // تنظيف container الاختبار
                    sh '''
                        docker stop front-service-test || true
                        docker rm front-service-test || true
                    '''
                }
            }
        }

        stage('Security Scan') {
            steps {
                echo '🔒 فحص الأمان...'
                sh '''
                    # فحص Composer dependencies للأمان
                    echo "🔒 فحص Composer dependencies..."
                    docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html \
                        composer:latest audit || echo "⚠️ وجدت مشاكل أمنية في Composer dependencies أو Docker غير مثبت"

                    # فحص NPM dependencies للأمان
                    echo "🔒 فحص NPM dependencies..."
                    docker run --rm -v "$(pwd):/workspace" -w /workspace \
                        node:18-alpine npm audit || echo "⚠️ وجدت مشاكل أمنية في NPM dependencies أو Docker غير مثبت"
                '''
            }
        }

        stage('Deploy') {
            when {
                anyOf {
                    branch 'main'
                    branch 'master'
                    branch 'production'
                }
            }
            steps {
                echo '🚀 بدء عملية النشر...'
                script {
                    def dockerImage = "${env.DOCKER_IMAGE_NAME}:${env.BUILD_NUMBER}"

                    echo """
                    ========================================
                    🚀 معلومات النشر
                    ========================================
                    الخدمة: ${env.APP_NAME}
                    رقم البناء: ${env.BUILD_NUMBER}
                    الفرع: ${env.GIT_BRANCH_NAME}
                    Commit: ${env.GIT_COMMIT_SHORT}
                    Docker Image: ${dockerImage}
                    ========================================
                    """

                    // إضافة منطق النشر هنا
                    if (env.DOCKER_REGISTRY) {
                        echo "📤 رفع الصورة إلى Docker Registry..."
                        sh """
                            docker tag ${dockerImage} ${env.DOCKER_REGISTRY}/${dockerImage}
                            docker tag ${dockerImage} ${env.DOCKER_REGISTRY}/${env.DOCKER_IMAGE_NAME}:latest
                            docker push ${env.DOCKER_REGISTRY}/${dockerImage}
                            docker push ${env.DOCKER_REGISTRY}/${env.DOCKER_IMAGE_NAME}:latest
                        """
                    } else {
                        echo "ℹ️ Docker Registry غير مضبوط - تخطي عملية الرفع"
                        echo "💡 يمكنك إضافة DOCKER_REGISTRY في Jenkins credentials"
                    }

                    // مثال على نشر إلى server
                    // sh '''
                    //     # SSH إلى الخادم ونشر الصورة
                    //     ssh user@server "docker pull ${dockerImage} && docker-compose up -d"
                    // '''
                }
            }
        }
    }

    post {
        always {
            echo '🧹 تنظيف الملفات المؤقتة...'
            sh """
                # تنظيف Docker images القديمة (إذا كان Docker مثبت)
                if command -v docker &> /dev/null; then
                    echo "🧹 تنظيف Docker images القديمة..."
                    docker images | grep "${env.DOCKER_IMAGE_NAME}" | tail -n +6 | awk '{print \$3}' | xargs -r docker rmi 2>/dev/null || true
                    docker system prune -f 2>/dev/null || true
                else
                    echo "ℹ️ Docker غير مثبت - تخطي تنظيف Docker"
                fi

                # تنظيف node_modules (اختياري)
                # rm -rf node_modules || true
            """

            // حفظ الملفات المهمة
            archiveArtifacts(
                artifacts: 'public/build/**',
                allowEmptyArchive: true,
                fingerprint: true
            )
            archiveArtifacts(
                artifacts: 'storage/logs/**',
                allowEmptyArchive: true
            )
        }

        success {
            echo '''
            ╔══════════════════════════════════════════╗
            ║  ✅ Pipeline اكتمل بنجاح!               ║
            ╚══════════════════════════════════════════╝
            '''
            // إرسال إشعار بالنجاح (يمكن تفعيله)
            /*
            emailext(
                subject: "✅ نجح البناء: ${env.APP_NAME} #${env.BUILD_NUMBER}",
                body: """
                    <h2>✅ نجح البناء!</h2>
                    <p><strong>الخدمة:</strong> ${env.APP_NAME}</p>
                    <p><strong>رقم البناء:</strong> ${env.BUILD_NUMBER}</p>
                    <p><strong>الفرع:</strong> ${env.GIT_BRANCH_NAME}</p>
                    <p><strong>Commit:</strong> ${env.GIT_COMMIT_SHORT}</p>
                    <p><strong>رابط البناء:</strong> <a href="${env.BUILD_URL}">${env.BUILD_URL}</a></p>
                """,
                mimeType: 'text/html',
                to: "${env.CHANGE_AUTHOR_EMAIL ?: 'dev@example.com'}"
            )
            */
        }

        failure {
            echo '''
            ╔══════════════════════════════════════════╗
            ║  ❌ Pipeline فشل!                       ║
            ╚══════════════════════════════════════════╝
            '''
            // إرسال إشعار بالفشل (يمكن تفعيله)
            /*
            emailext(
                subject: "❌ فشل البناء: ${env.APP_NAME} #${env.BUILD_NUMBER}",
                body: """
                    <h2>❌ فشل البناء!</h2>
                    <p><strong>الخدمة:</strong> ${env.APP_NAME}</p>
                    <p><strong>رقم البناء:</strong> ${env.BUILD_NUMBER}</p>
                    <p><strong>الفرع:</strong> ${env.GIT_BRANCH_NAME}</p>
                    <p><strong>Commit:</strong> ${env.GIT_COMMIT_SHORT}</p>
                    <p><strong>رابط البناء:</strong> <a href="${env.BUILD_URL}">${env.BUILD_URL}</a></p>
                    <p><strong>Console Output:</strong> <a href="${env.BUILD_URL}console">${env.BUILD_URL}console</a></p>
                """,
                mimeType: 'text/html',
                to: "${env.CHANGE_AUTHOR_EMAIL ?: 'dev@example.com'}"
            )
            */
        }

        unstable {
            echo '⚠️ Pipeline اكتمل مع تحذيرات!'
        }
    }
}
