# CodeNotes - Tài liệu tích hợp Frontend

Cập nhật theo source hiện tại ngày 2026-06-29.

Tài liệu này dành cho frontend đọc nhanh để biết backend đã làm xong phần nào, cần gọi API ra sao, response trả về thế nào và nên dựng các màn hình nào trước.

## 1. Tổng quan dự án

CodeNotes hiện là backend PHP REST API, chưa thấy mã frontend trong repo.

Backend đang có:

- Router PHP tự viết, nhận request và gọi controller theo route.
- API JSON với response chuẩn cho thành công và lỗi.
- Kết nối MySQL qua PDO.
- Đọc biến môi trường bằng `vlucas/phpdotenv`.
- Gửi email xác thực bằng `PHPMailer`.
- Đăng ký tài khoản.
- Xác thực email bằng mã 6 số.
- Đăng nhập sau khi email đã xác thực.
- Tạo và kiểm tra JWT.
- Middleware bảo vệ endpoint bằng `Authorization: Bearer <token>`.
- Endpoint mẫu/danh sách user có bảo vệ JWT.

Chưa thấy hoặc chưa hoàn chỉnh:

- Chưa có frontend.
- Chưa có chức năng ghi chú/code notes thật sự như tạo note, sửa note, xóa note, tìm kiếm note.
- Chưa có logout phía server. Frontend chỉ cần xóa token local.
- Chưa có refresh token.
- Chưa có resend verification code.
- Chưa có forgot/reset password.
- Chưa có CORS header trong backend. Nếu frontend chạy khác origin, backend cần bổ sung CORS hoặc chạy frontend cùng origin.
- `POST /users` và `GET /users/{id}` hiện là endpoint mẫu để demo JWT, chưa phải CRUD user hoàn chỉnh.

## 2. Base URL

Các endpoint trong backend được khai báo theo path:

```text
/auth/register
/auth/verify-email
/auth/login
/users
/users/{id}
```

Frontend nên cấu hình một biến môi trường:

```text
VITE_API_BASE_URL=http://codenotes.test
```

Hoặc dùng base URL tương ứng với môi trường local của bạn. Điều quan trọng là khi request tới backend, PHP router phải nhận path bắt đầu bằng `/auth/...` hoặc `/users/...`.

Ví dụ:

```text
POST {VITE_API_BASE_URL}/auth/register
POST {VITE_API_BASE_URL}/auth/login
GET  {VITE_API_BASE_URL}/users
```

Nếu chạy bằng WAMP dưới thư mục con kiểu `http://localhost/codenotes/auth/register`, cần kiểm tra lại vì router hiện match đúng `/auth/register`, không tự bỏ prefix `/codenotes`.

## 3. Header chung

Với request JSON:

```http
Content-Type: application/json
Accept: application/json
```

Với endpoint cần đăng nhập:

```http
Authorization: Bearer <jwt_token>
```

Token lấy từ response của API login.

## 4. Response chuẩn

### 4.1. Thành công

Backend dùng format:

```json
{
  "success": true,
  "message": "Success message",
  "data": {}
}
```

Frontend nên đọc:

- `success`: kiểm tra API thành công theo format app.
- `message`: hiển thị toast/message khi cần.
- `data`: dữ liệu chính.

### 4.2. Thất bại

Backend dùng format:

```json
{
  "success": false,
  "message": "Error message",
  "errors": {}
}
```

Frontend nên đọc:

- HTTP status code để xử lý loại lỗi.
- `message` để hiển thị lỗi chính.
- `errors` để lấy chi tiết phụ, ví dụ `remaining_attempts`.

Một số lỗi từ router/global handler có thể trả format khác:

```json
{
  "error": "Endpoint not found"
}
```

Frontend nên có fallback nếu response không có `success`.

## 5. Data model frontend cần biết

### 5.1. User public

```ts
type User = {
  id: number | string;
  username: string;
  email: string;
  email_verified_at: string | null;
  created_at?: string;
};
```

Lưu ý:

- Backend không trả `password_hash` trong các response public.
- `email_verified_at = null` nghĩa là user chưa xác thực email.
- Sau login thành công, user chắc chắn đã xác thực email.

### 5.2. Auth token response

```ts
type LoginData = {
  token: string;
  token_type: "Bearer";
  expires_in: number;
  user: User;
};
```

`expires_in` tính bằng giây. Frontend nên tự tính thời điểm hết hạn:

```ts
const expiresAt = Date.now() + data.expires_in * 1000;
```

### 5.3. JWT payload

Backend tạo JWT có payload dạng:

```json
{
  "iss": "APP_URL hoặc codenotes-api",
  "iat": 1710000000,
  "nbf": 1710000000,
  "exp": 1710003600,
  "sub": "1",
  "user": {
    "id": 1,
    "username": "demo",
    "email": "demo@example.com",
    "email_verified_at": "2026-06-29 10:00:00"
  }
}
```

JWT được ký, không được mã hóa. Frontend không đưa dữ liệu nhạy cảm vào token và không coi payload là bí mật.

## 6. Các chức năng đã hoàn thành

| Chức năng | Trạng thái | Ghi chú cho frontend |
|---|---:|---|
| Đăng ký tài khoản | Đã có | Gọi `POST /auth/register`, sau đó chuyển sang màn xác thực email. |
| Gửi email mã xác thực | Đã có | Backend dùng SMTP/PHPMailer. Dev mode có thể trả `verification_code_for_dev` nếu gửi mail thất bại. |
| Xác thực email | Đã có | Gọi `POST /auth/verify-email` với email và code 6 số. |
| Chặn login khi chưa verify email | Đã có | Login trả `403` nếu email chưa xác thực. |
| Đăng nhập | Đã có | Gọi `POST /auth/login`, nhận JWT. |
| JWT middleware | Đã có | Endpoint protected cần `Authorization: Bearer <token>`. |
| Lấy danh sách users public | Đã có | `GET /users`, cần JWT. |
| Endpoint demo user store/show | Có mẫu | Dùng để test JWT, chưa phải chức năng user CRUD hoàn chỉnh. |

## 7. API chi tiết

### 7.1. Đăng ký

```http
POST /auth/register
```

Auth: không cần token.

Body:

```json
{
  "username": "Nguyen Van A",
  "email": "a@example.com",
  "password": "123456"
}
```

Validation backend:

- `username`, `email`, `password` bắt buộc.
- `email` phải đúng định dạng.
- `password` tối thiểu 6 ký tự.
- Email không được trùng.

Success `201`:

```json
{
  "success": true,
  "message": "User registered successfully. Please check your email for the verification code.",
  "data": {
    "user": {
      "id": "1",
      "username": "Nguyen Van A",
      "email": "a@example.com",
      "email_verified_at": null
    },
    "verification_expires_at": "2026-06-29 10:15:00",
    "mail_sent": true
  }
}
```

Dev mode khi SMTP lỗi có thể trả thêm:

```json
{
  "verification_code_for_dev": "123456"
}
```

Frontend flow:

- Submit form đăng ký.
- Nếu thành công, chuyển sang màn nhập mã xác thực.
- Giữ email vừa đăng ký để gọi API verify.
- Nếu có `verification_code_for_dev`, có thể hiển thị nhỏ cho môi trường dev hoặc tự fill vào ô code.

Lỗi thường gặp:

| HTTP | Message | Cách xử lý UI |
|---:|---|---|
| 400 | `Invalid JSON body` | Kiểm tra request body. |
| 422 | `Username, email and password are required` | Highlight field thiếu. |
| 422 | `Invalid email` | Báo email không hợp lệ. |
| 422 | `Password must be at least 6 characters` | Báo password quá ngắn. |
| 409 | `Email already exists` | Gợi ý đăng nhập hoặc dùng email khác. |
| 500 | `Cannot send verification email` | Báo lỗi gửi email, cho thử lại sau. |
| 500 | `Cannot create account` | Báo lỗi server. |

### 7.2. Xác thực email

```http
POST /auth/verify-email
```

Auth: không cần token.

Body:

```json
{
  "email": "a@example.com",
  "code": "123456"
}
```

Validation backend:

- `email`, `code` bắt buộc.
- `email` phải đúng định dạng.
- `code` phải là đúng 6 chữ số.
- Code hết hạn sau khoảng 15 phút kể từ lúc đăng ký.
- Nhập sai tối đa 5 lần.

Success `200`:

```json
{
  "success": true,
  "message": "Email verified successfully",
  "data": {
    "user": {
      "id": "1",
      "username": "Nguyen Van A",
      "email": "a@example.com",
      "email_verified_at": "2026-06-29 10:05:00"
    }
  }
}
```

Nếu email đã verify trước đó, backend vẫn trả success:

```json
{
  "success": true,
  "message": "Email already verified",
  "data": {
    "user": {}
  }
}
```

Frontend flow:

- Màn verify gồm email và code 6 số.
- Nếu user vừa đăng ký, prefill email.
- Khi verify thành công, chuyển sang màn login.
- Không tự login vì API verify hiện chưa trả token.

Lỗi thường gặp:

| HTTP | Message | Cách xử lý UI |
|---:|---|---|
| 400 | `Invalid JSON body` | Kiểm tra request body. |
| 422 | `Email and verification code are required` | Highlight field thiếu. |
| 422 | `Invalid email` | Báo email không hợp lệ. |
| 422 | `Verification code must be 6 digits` | Chỉ cho nhập 6 số. |
| 404 | `Verification request not found` | Gợi ý đăng ký lại hoặc kiểm tra email. |
| 422 | `Verification code expired` | Hiện chưa có resend API, báo user liên hệ hoặc đăng ký lại tùy nghiệp vụ. |
| 422 | `Invalid verification code` | Hiển thị số lần còn lại nếu có. |
| 429 | `Too many wrong attempts. Please request a new verification code.` | Khóa form hoặc yêu cầu luồng cấp mã mới khi backend bổ sung. |
| 500 | `Cannot verify email` | Báo lỗi server. |

Khi nhập sai code, backend trả:

```json
{
  "success": false,
  "message": "Invalid verification code",
  "errors": {
    "remaining_attempts": 4
  }
}
```

### 7.3. Đăng nhập

```http
POST /auth/login
```

Auth: không cần token.

Body:

```json
{
  "email": "a@example.com",
  "password": "123456"
}
```

Validation backend:

- `email`, `password` bắt buộc.
- `email` phải đúng định dạng.
- Email/password phải đúng.
- Email phải được xác thực.

Success `200`:

```json
{
  "success": true,
  "message": "Login successfully",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "Bearer",
    "expires_in": 3600,
    "user": {
      "id": "1",
      "username": "Nguyen Van A",
      "email": "a@example.com",
      "email_verified_at": "2026-06-29 10:05:00"
    }
  }
}
```

Frontend flow:

- Submit email/password.
- Lưu `token`, `user`, `expiresAt`.
- Chuyển vào khu vực đã đăng nhập.
- Các request protected gắn header `Authorization`.

Lỗi thường gặp:

| HTTP | Message | Cách xử lý UI |
|---:|---|---|
| 400 | `Invalid JSON body` | Kiểm tra request body. |
| 422 | `Email and password are required` | Highlight field thiếu. |
| 422 | `Invalid email` | Báo email không hợp lệ. |
| 401 | `Invalid credentials` | Báo sai email hoặc mật khẩu. |
| 403 | `Please verify your email before login` | Chuyển sang màn verify email. |

### 7.4. Lấy danh sách users

```http
GET /users
```

Auth: cần token.

Header:

```http
Authorization: Bearer <jwt_token>
```

Success `200`:

```json
{
  "success": true,
  "message": "Token valid",
  "data": {
    "request_user": {
      "id": 1,
      "username": "Nguyen Van A",
      "email": "a@example.com",
      "email_verified_at": "2026-06-29 10:05:00"
    },
    "token_payload": {
      "iss": "codenotes-api",
      "iat": 1710000000,
      "nbf": 1710000000,
      "exp": 1710003600,
      "sub": "1",
      "user": {}
    },
    "users": [
      {
        "id": 1,
        "username": "Nguyen Van A",
        "email": "a@example.com",
        "email_verified_at": "2026-06-29 10:05:00",
        "created_at": "2026-06-29 10:00:00"
      }
    ]
  }
}
```

Frontend có thể dùng endpoint này để test trạng thái đăng nhập và hiển thị danh sách user đơn giản.

Lỗi auth:

| HTTP | Message | Cách xử lý UI |
|---:|---|---|
| 401 | `Missing Bearer token` | Xóa session frontend, chuyển login. |
| 401 | `Invalid or expired token` | Xóa session frontend, chuyển login. |

### 7.5. Endpoint mẫu tạo user

```http
POST /users
```

Auth: cần token.

Hiện endpoint này chưa tạo user thật. Nó chỉ trả dữ liệu demo để chứng minh JWT middleware hoạt động.

Success `200`:

```json
{
  "success": true,
  "message": "Token valid",
  "data": {
    "request_user": {},
    "token_payload": {},
    "message_for_example": "Day la cach dung JWT trong store() de bao ve API"
  }
}
```

Frontend không nên dùng endpoint này như chức năng production.

### 7.6. Endpoint mẫu chi tiết user

```http
GET /users/{id}
```

Auth: cần token.

Ví dụ:

```http
GET /users/1
```

Success `200`:

```json
{
  "success": true,
  "message": "User detail example",
  "data": {
    "id": "1",
    "request_user_id": 1,
    "message_for_example": "Day la endpoint mau cho show()"
  }
}
```

Frontend không nên coi đây là API chi tiết user hoàn chỉnh.

## 8. Frontend nên dựng các màn hình nào

### 8.1. Register page

Fields:

- `username`
- `email`
- `password`
- `confirmPassword` nên validate ở frontend, backend chưa cần field này.

Validate frontend:

- Username không rỗng.
- Email đúng định dạng.
- Password tối thiểu 6 ký tự.
- Confirm password trùng password.

Submit:

- Gọi `POST /auth/register`.
- Thành công thì chuyển sang verify page, truyền email.

### 8.2. Verify email page

Fields:

- `email`
- `code`

Validate frontend:

- Email đúng định dạng.
- Code chỉ gồm 6 chữ số.

Submit:

- Gọi `POST /auth/verify-email`.
- Thành công thì chuyển sang login page.
- Nếu lỗi `remaining_attempts`, hiển thị số lần còn lại.

### 8.3. Login page

Fields:

- `email`
- `password`

Submit:

- Gọi `POST /auth/login`.
- Thành công lưu session.
- Nếu `403`, chuyển sang verify email page và prefill email.

### 8.4. Authenticated layout

Nên có:

- Header hiển thị username/email.
- Logout button. Logout frontend bằng cách xóa token/user khỏi storage.
- Guard route: nếu không có token hoặc token hết hạn theo `expiresAt`, chuyển login.

### 8.5. Users page

Nên có:

- Gọi `GET /users`.
- Hiển thị danh sách `users`.
- Dùng page này để kiểm tra token protected request.

## 9. Gợi ý auth storage

Cách đơn giản:

```ts
type AuthSession = {
  token: string;
  tokenType: "Bearer";
  expiresAt: number;
  user: User;
};
```

Lưu vào `localStorage`:

```ts
localStorage.setItem("codenotes_auth", JSON.stringify(session));
```

Đọc lại khi app khởi động:

```ts
const raw = localStorage.getItem("codenotes_auth");
const session = raw ? JSON.parse(raw) as AuthSession : null;

if (!session || Date.now() >= session.expiresAt) {
  localStorage.removeItem("codenotes_auth");
}
```

Lưu ý bảo mật:

- Không lưu password.
- Không log token ra console ở production.
- Nếu app yêu cầu bảo mật cao hơn, cân nhắc lưu token trong memory thay vì localStorage.

## 10. API client mẫu bằng fetch

```ts
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL ?? "http://codenotes.test";
const AUTH_STORAGE_KEY = "codenotes_auth";

type ApiError = {
  status: number;
  message: string;
  errors?: Record<string, unknown>;
  payload?: unknown;
};

function getAuthSession() {
  const raw = localStorage.getItem(AUTH_STORAGE_KEY);
  if (!raw) return null;

  try {
    const session = JSON.parse(raw);
    if (!session.expiresAt || Date.now() >= session.expiresAt) {
      localStorage.removeItem(AUTH_STORAGE_KEY);
      return null;
    }
    return session;
  } catch {
    localStorage.removeItem(AUTH_STORAGE_KEY);
    return null;
  }
}

async function apiRequest<T>(
  path: string,
  options: RequestInit & { auth?: boolean } = {}
): Promise<T> {
  const headers = new Headers(options.headers);
  headers.set("Accept", "application/json");

  if (options.body && !headers.has("Content-Type")) {
    headers.set("Content-Type", "application/json");
  }

  if (options.auth) {
    const session = getAuthSession();
    if (session?.token) {
      headers.set("Authorization", `Bearer ${session.token}`);
    }
  }

  const response = await fetch(`${API_BASE_URL}${path}`, {
    ...options,
    headers,
  });

  const payload = await response.json().catch(() => null);

  if (!response.ok || payload?.success === false) {
    const error: ApiError = {
      status: response.status,
      message: payload?.message ?? payload?.error ?? "Request failed",
      errors: payload?.errors,
      payload,
    };

    if (response.status === 401) {
      localStorage.removeItem(AUTH_STORAGE_KEY);
    }

    throw error;
  }

  return payload.data as T;
}
```

Auth functions:

```ts
export function register(input: {
  username: string;
  email: string;
  password: string;
}) {
  return apiRequest<{
    user: User;
    verification_expires_at: string;
    mail_sent: boolean;
    verification_code_for_dev?: string;
  }>("/auth/register", {
    method: "POST",
    body: JSON.stringify(input),
  });
}

export function verifyEmail(input: {
  email: string;
  code: string;
}) {
  return apiRequest<{ user: User }>("/auth/verify-email", {
    method: "POST",
    body: JSON.stringify(input),
  });
}

export async function login(input: {
  email: string;
  password: string;
}) {
  const data = await apiRequest<LoginData>("/auth/login", {
    method: "POST",
    body: JSON.stringify(input),
  });

  localStorage.setItem(AUTH_STORAGE_KEY, JSON.stringify({
    token: data.token,
    tokenType: data.token_type,
    expiresAt: Date.now() + data.expires_in * 1000,
    user: data.user,
  }));

  return data;
}

export function logout() {
  localStorage.removeItem(AUTH_STORAGE_KEY);
}

export function getUsers() {
  return apiRequest<{
    request_user: User;
    token_payload: Record<string, unknown>;
    users: User[];
  }>("/users", {
    method: "GET",
    auth: true,
  });
}
```

## 11. Checklist code frontend

Nên làm trước:

- Tạo `.env` frontend có `VITE_API_BASE_URL`.
- Tạo API client dùng chung.
- Tạo auth store/context.
- Tạo route guard cho trang cần đăng nhập.
- Tạo register page.
- Tạo verify email page.
- Tạo login page.
- Tạo users page để test token.
- Tạo logout button.
- Chuẩn hóa toast/error message từ `message` và `errors`.

Nên chờ backend bổ sung trước khi code sâu:

- Notes CRUD.
- Resend verification code.
- Forgot/reset password.
- Refresh token.
- User profile update.

## 12. Ghi chú backend để phối hợp với frontend

### 12.1. CORS

Backend hiện chỉ set:

```php
header('Content-Type: application/json');
```

Nếu frontend chạy ở origin khác, ví dụ `http://localhost:5173`, trình duyệt có thể bị CORS. Cần backend bổ sung các header CORS và xử lý request `OPTIONS`.

### 12.2. Database

File SQL hiện có:

```sql
ALTER TABLE users
    ADD COLUMN email_verified_at DATETIME NULL DEFAULT NULL AFTER email;
```

Source có model `EmailVerification`, nên database cần bảng `email_verifications` với các cột tối thiểu:

- `id`
- `user_id`
- `email`
- `code_hash`
- `expires_at`
- `attempt_count`
- `verified_at`
- `created_at`
- `updated_at`

Tài liệu này không thấy file create table đầy đủ cho `users` và `email_verifications`, nên khi frontend test API mà gặp lỗi 500, cần kiểm tra schema database trước.

### 12.3. Base path khi chạy WAMP

Router đang dispatch bằng:

```php
parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
```

Vì vậy path request cần khớp route như `/auth/login`. Nếu app được mount dưới subfolder `/codenotes`, có thể backend nhận `/codenotes/auth/login` và route không match. Cách ổn định nhất là tạo virtual host trỏ vào project để API path bắt đầu từ `/auth/...`.

## 13. Luồng người dùng đề xuất

```text
Register
  -> POST /auth/register
  -> Verify Email
      -> POST /auth/verify-email
      -> Login
          -> POST /auth/login
          -> Lưu token/user
          -> Users/Test Protected Page
              -> GET /users với Bearer token
```

Khi token hết hạn hoặc invalid:

```text
Protected API trả 401
  -> Xóa auth session frontend
  -> Chuyển về Login
```

## 14. Kết luận cho frontend

Frontend hiện có thể code chắc các phần sau:

- Register.
- Verify email.
- Login.
- Auth state/token handling.
- Protected route.
- Logout phía client.
- Trang test danh sách users.

Frontend chưa nên code nghiệp vụ CodeNotes chính nếu cần lưu note thật, vì backend hiện chưa có Notes API.
