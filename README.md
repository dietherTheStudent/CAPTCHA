# Face Scan Authentication System
Flask + OpenCV + face_recognition

## Setup (run these once)

```bash
pip install flask opencv-python face-recognition numpy
```

> On some systems `face-recognition` requires `cmake` and `dlib` first:
> ```bash
> pip install cmake dlib
> pip install face-recognition
> ```

## Run

```bash
python app.py
```

Then open: http://127.0.0.1:5000

## How it works

1. **Register** — Go to `/register`, enter a username, capture your face → saved to `users.json`
2. **Login** — Go to `/login`, look at webcam, click Scan → compares face encoding against stored users
3. **Dashboard** — Shows on successful authentication

## File Structure

```
face_auth/
├── app.py              # Flask backend (all routes + face logic)
├── requirements.txt
├── users.json          # Auto-created; stores face encodings
└── templates/
    ├── base.html       # Shared layout
    ├── index.html      # Home page
    ├── register.html   # Registration (webcam + username)
    ├── login.html      # Login (webcam face scan)
    └── dashboard.html  # Post-login page
```

## Key API Routes

| Route | Method | Description |
|-------|--------|-------------|
| `/` | GET | Home page |
| `/register` | GET | Register page |
| `/login` | GET | Login page |
| `/dashboard` | GET | Protected dashboard |
| `/api/register` | POST | Encodes and saves face |
| `/api/login` | POST | Matches face, creates session |
| `/api/logout` | POST | Clears session |
