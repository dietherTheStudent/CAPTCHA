from flask import Flask, render_template, request, jsonify, session
import face_recognition
import numpy as np
import base64
import json
import os
import cv2
from datetime import datetime

app = Flask(__name__)
app.secret_key = "face_auth_secret_key"

# Simple file-based storage (no MySQL needed for demo)
USERS_FILE = "users.json"

def load_users():
    if os.path.exists(USERS_FILE):
        with open(USERS_FILE, "r") as f:
            return json.load(f)
    return {}

def save_users(users):
    with open(USERS_FILE, "w") as f:
        json.dump(users, f)

def decode_image(data_url):
    """Convert base64 image from browser to numpy array."""
    header, encoded = data_url.split(",", 1)
    image_data = base64.b64decode(encoded)
    np_arr = np.frombuffer(image_data, np.uint8)
    img = cv2.imdecode(np_arr, cv2.IMREAD_COLOR)
    return img

def get_face_encoding(img):
    """Extract face encoding from image. Returns encoding or None."""
    rgb = cv2.cvtColor(img, cv2.COLOR_BGR2RGB)
    encodings = face_recognition.face_encodings(rgb)
    if len(encodings) == 0:
        return None
    return encodings[0]

@app.route("/")
def index():
    return render_template("index.html")

@app.route("/register", methods=["GET"])
def register_page():
    return render_template("register.html")

@app.route("/login", methods=["GET"])
def login_page():
    return render_template("login.html")

@app.route("/dashboard")
def dashboard():
    if "username" not in session:
        return render_template("login.html")
    return render_template("dashboard.html", username=session["username"])

@app.route("/api/register", methods=["POST"])
def api_register():
    data = request.json
    username = data.get("username", "").strip()
    image_data = data.get("image")

    if not username or not image_data:
        return jsonify({"success": False, "message": "Username and face image required."})

    users = load_users()
    if username in users:
        return jsonify({"success": False, "message": "Username already exists."})

    img = decode_image(image_data)
    encoding = get_face_encoding(img)

    if encoding is None:
        return jsonify({"success": False, "message": "No face detected. Please try again."})

    users[username] = {
        "encoding": encoding.tolist(),
        "registered_at": datetime.now().isoformat()
    }
    save_users(users)

    return jsonify({"success": True, "message": f"User '{username}' registered successfully!"})

@app.route("/api/login", methods=["POST"])
def api_login():
    data = request.json
    image_data = data.get("image")

    if not image_data:
        return jsonify({"success": False, "message": "No image received."})

    img = decode_image(image_data)
    unknown_encoding = get_face_encoding(img)

    if unknown_encoding is None:
        return jsonify({"success": False, "message": "No face detected. Please try again."})

    users = load_users()
    if not users:
        return jsonify({"success": False, "message": "No registered users found."})

    for username, user_data in users.items():
        known_encoding = np.array(user_data["encoding"])
        results = face_recognition.compare_faces([known_encoding], unknown_encoding, tolerance=0.5)
        distance = face_recognition.face_distance([known_encoding], unknown_encoding)[0]
        confidence = round((1 - distance) * 100, 1)

        if results[0]:
            session["username"] = username
            return jsonify({
                "success": True,
                "message": f"Welcome back, {username}!",
                "username": username,
                "confidence": confidence
            })

    return jsonify({"success": False, "message": "Face not recognized. Access denied."})

@app.route("/api/logout", methods=["POST"])
def api_logout():
    session.clear()
    return jsonify({"success": True})

if __name__ == "__main__":
    app.run(debug=True)
