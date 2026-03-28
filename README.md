# Test Site

순수 PHP, PostgreSQL, Bootstrap, Vue CDN 기반의 회원 관리 + 게시판 사이트입니다.

## 실행

```bash
cp .env.example .env
docker compose up --build -d
```

사이트:
- `http://localhost:8080`

간단 검증:

```bash
sh tests/smoke.sh
```

## 구조

- `public/index.php`: 모든 웹 요청 진입점
- `public/api.php`: 모든 백엔드 API 진입점
- `app/Entity`: 엔티티
- `app/Repository`: 데이터 접근
- `app/Service`: 비즈니스 로직
- `app/Controller`: API 컨트롤러
- `deploy/dokploy/`: Dokploy 배포 자산
