# حل مشكلة Docker في Jenkins على macOS

## المشكلة:
Jenkins container لا يستطيع الوصول إلى Docker لأن Docker client غير مثبت داخل Jenkins container.

## الحل:

### 1. تثبيت Docker client داخل Jenkins container:

```bash
# تثبيت Docker client
docker exec -u root jenkins sh -c "curl -fsSL https://get.docker.com | sh"
```

### 2. أو إعادة بناء Jenkins container مع Docker client:

```bash
# إيقاف Jenkins
docker stop jenkins && docker rm jenkins

# إنشاء Dockerfile مخصص
cat > Dockerfile.jenkins <<EOF
FROM jenkins/jenkins:lts
USER root
RUN curl -fsSL https://get.docker.com | sh
USER jenkins
EOF

# بناء الصورة
docker build -t jenkins-docker:lts -f Dockerfile.jenkins .

# تشغيل Jenkins
docker run -d --name jenkins \
  -v /var/run/docker.sock:/var/run/docker.sock \
  -v jenkins_home:/var/jenkins_home \
  -p 8080:8080 \
  -p 50000:50000 \
  jenkins-docker:lts
```

### 3. أو استخدام Docker-in-Docker:

```bash
docker run -d --name jenkins \
  -v /var/run/docker.sock:/var/run/docker.sock \
  -v jenkins_home:/var/jenkins_home \
  -p 8080:8080 \
  -p 50000:50000 \
  --privileged \
  jenkins/jenkins:lts
```

## التحقق:

```bash
# تأكد من أن Docker يعمل داخل Jenkins
docker exec jenkins docker ps
docker exec jenkins docker --version
```

## ملاحظة:
بعد تثبيت Docker client، يجب أن يعمل Pipeline بشكل صحيح!

