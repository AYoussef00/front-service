# إعداد Jenkins على macOS مع Docker

## الحل السريع:

### إذا كان Jenkins يعمل داخل Docker container:

```bash
# أوقف Jenkins container الحالي (إن وجد)
docker stop jenkins 2>/dev/null || true
docker rm jenkins 2>/dev/null || true

# شغّل Jenkins مع Docker socket
docker run -d \
  --name jenkins \
  -v /var/run/docker.sock:/var/run/docker.sock \
  -v jenkins_home:/var/jenkins_home \
  -p 8080:8080 \
  jenkins/jenkins:lts
```

### أو إذا كان Jenkins يعمل مباشرة على macOS:

تأكد من أن Docker Desktop يعمل ويمكن الوصول إليه من Jenkins.

## التحقق:

```bash
# تأكد من أن Docker Desktop يعمل
docker ps

# تأكد من أن Jenkins يستطيع الوصول لـ Docker
# (من داخل Jenkins container أو Jenkins نفسه)
docker exec jenkins docker ps
```

## ملاحظة:

على macOS، Docker socket موجود في:
- `/var/run/docker.sock` (لـ Docker Desktop)

إذا كان Jenkins يعمل مباشرة على macOS وليس داخل container، يجب أن يكون Jenkins قادراً على الوصول إلى Docker Desktop.

