<?php
session_start();
$API_BASE = "http://127.0.0.1:8080";

// Handle session set
if(isset($_GET['set_session']) && !empty($_GET['username'])) {
    $_SESSION['username'] = $_GET['username'];
    header("Location: index.php");
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Spreadsheet Chatbot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        #chat { border:1px solid #ccc; height:300px; overflow-y:scroll; padding:10px; margin-top:20px; }
        .user { color:blue; }
        .bot { color:green; }
        #savedList { border:1px solid #ccc; padding:10px; max-height:200px; overflow-y:auto; margin-bottom:10px; }
        .savedItem { cursor:pointer; padding:5px; border-bottom:1px solid #eee; }
        .savedItem:hover { background-color:#f0f0f0; }
        #sheetSelection { margin-top:10px; }
    </style>
</head>
<body class="bg-light">

<div class="container py-5">

<?php if (!isset($_SESSION['username'])): ?>
    <!-- LOGIN & SIGNUP -->
    <div class="row justify-content-center">
        <div class="col-md-6">
            <ul class="nav nav-tabs mb-3" id="authTab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button">Login</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="signup-tab" data-bs-toggle="tab" data-bs-target="#signup" type="button">Sign Up</button>
                </li>
            </ul>
            <div class="tab-content">
                <!-- LOGIN -->
                <div class="tab-pane fade show active" id="login">
                    <form id="loginForm" autocomplete="off">
                        <div class="mb-3">
                            <label>Username</label>
                            <input type="text" class="form-control" id="loginUsername" required>
                        </div>
                        <div class="mb-3">
                            <label>Password</label>
                            <input type="password" class="form-control" id="loginPassword" required>
                        </div>
                        <button class="btn btn-primary w-100">Login</button>
                        <div id="loginMsg" class="mt-2 text-danger"></div>
                    </form>
                </div>

                <!-- SIGNUP -->
                <div class="tab-pane fade" id="signup">
                    <form id="signupForm" autocomplete="off">
                        <div class="mb-3">
                            <label>Username</label>
                            <input type="text" class="form-control" id="signupUsername" required>
                        </div>
                        <div class="mb-3">
                            <label>Password</label>
                            <input type="password" class="form-control" id="signupPassword" required>
                        </div>
                        <button class="btn btn-success w-100">Sign Up</button>
                        <div id="signupMsg" class="mt-2 text-danger"></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- LOGGED IN INTERFACE -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></h2>
        <a href="?logout=1" class="btn btn-danger">Logout</a>
    </div>

    <button class="btn btn-info mb-3" onclick="loadSavedChatbots()">Preview Saved Chatbots</button>
    <div id="savedList" class="mb-3" style="display:none;"></div>

    <!-- Chatbot Form -->
    <form id="chatbotForm">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label>Chatbot Name</label>
                <input type="text" class="form-control" id="chatbot_name" required>
            </div>
            <div class="col-md-6 mb-3">
                <label>Chatbot ID</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="chatbot_id" readonly>
                    <button type="button" class="btn btn-secondary" onclick="generateID()">Generate ID</button>
                </div>
            </div>
        </div>

        <div class="mb-3">
            <label>Gemini API Key</label>
            <input type="text" class="form-control" id="gemini_api_key" required>
        </div>

        <div class="mb-3">
            <label>Gemini Model</label>
            <input type="text" class="form-control" id="gemini_model" value="gemini-2.0-flash" required>
        </div>

        <div class="mb-3">
            <label>Google Spreadsheet ID</label>
            <input type="text" class="form-control" id="sheet_id" required>
        </div>

        <div class="mb-3">
            <label>Service Account JSON</label>
            <textarea class="form-control" id="service_account_json" rows="5" required></textarea>
        </div>

        <button type="button" class="btn btn-primary mb-3" onclick="connectSpreadsheet()">Connect</button>

        <div id="sheetSelection" class="mt-3"></div>

        <button type="button" class="btn btn-success mt-2" id="loadChatBtn" style="display:none;" onclick="loadChat()">Load to Chat</button>
    </form>

    <div id="chatInterface" class="mt-3" style="display:none;">
        <h4>Chat Interface</h4>
        <div id="chat"></div>
        <div class="input-group mt-2">
            <input type="text" id="user_input" class="form-control" placeholder="Ask about spreadsheet...">
            <button class="btn btn-success" onclick="sendMessage()">Send</button>
        </div>
        <button class="btn btn-success mt-3" onclick="saveChatbot()">Save Chatbot</button>
    </div>
<?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const API_BASE = "<?= $API_BASE ?>";

<?php if (!isset($_SESSION['username'])): ?>
// LOGIN & SIGNUP
document.getElementById('loginForm').onsubmit = async (e)=>{
    e.preventDefault();
    const username = document.getElementById('loginUsername').value;
    const password = document.getElementById('loginPassword').value;
    const res = await fetch(`${API_BASE}/login`, {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({username, password})
    });
    const data = await res.json();
    if(data.success){
        window.location = `?set_session=1&username=${username}`;
    } else {
        document.getElementById('loginMsg').textContent = data.message;
    }
};

document.getElementById('signupForm').onsubmit = async (e)=>{
    e.preventDefault();
    const username = document.getElementById('signupUsername').value;
    const password = document.getElementById('signupPassword').value;
    const res = await fetch(`${API_BASE}/signup`, {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({username, password})
    });
    const data = await res.json();
    if(data.success){
        window.location = `?set_session=1&username=${username}`;
    } else {
        document.getElementById('signupMsg').textContent = data.message;
    }
};
<?php else: ?>

function generateID() {
    document.getElementById('chatbot_id').value = 'cb-' + Math.random().toString(36).substring(2,10);
}

// Connect Service Account and list sheets
async function connectSpreadsheet() {
    const data = new URLSearchParams({
        username:"<?= $_SESSION['username'] ?>",
        chatbot_name: document.getElementById('chatbot_name').value,
        chatbot_id: document.getElementById('chatbot_id').value,
        gemini_api_key: document.getElementById('gemini_api_key').value,
        gemini_model: document.getElementById('gemini_model').value,
        sheet_id: document.getElementById('sheet_id').value,
        service_account_json: document.getElementById('service_account_json').value
    });

    const res = await fetch(`${API_BASE}/set_credentials`, { method:'POST', body:data });
    if(!res.ok) return alert("Failed to connect spreadsheet");

    const json = await res.json();
    const container = document.getElementById('sheetSelection');
    container.innerHTML = "";

    json.sheets.forEach(name => {
        const div = document.createElement('div');
        div.innerHTML = `<input type="checkbox" name="sheet_names" value="${name}"> ${name}`;
        container.appendChild(div);
    });

    document.getElementById('loadChatBtn').style.display = 'inline-block';
}

// Load selected sheets to chat
function loadChat() {
    const selectedSheets = Array.from(document.querySelectorAll('input[name="sheet_names"]:checked'))
                                .map(el=>el.value);
    if(selectedSheets.length === 0) return alert("Select at least one sheet");

    const data = new URLSearchParams();
    selectedSheets.forEach(s => data.append('sheet_names', s));

    fetch(`${API_BASE}/set_sheet`, { method:'POST', body:data })
        .then(res => res.json())
        .then(json => {
            document.getElementById('chatInterface').style.display='block';
            alert("Sheets loaded! You can now chat.");
        });
}

// Send message to chat
async function sendMessage() {
    const input = document.getElementById('user_input').value;
    if(!input) return;
    const chatDiv = document.getElementById('chat');
    chatDiv.innerHTML += `<p class="user"><b>You:</b> ${input}</p>`;

    const res = await fetch(`${API_BASE}/chat`, {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({message: input})
    });
    const data = await res.json();
    chatDiv.innerHTML += `<p class="bot"><b>Bot:</b> ${data.response}</p>`;
    chatDiv.scrollTop = chatDiv.scrollHeight;
    document.getElementById('user_input').value = '';
}

// Save chatbot
async function saveChatbot() {
    const selectedSheets = Array.from(document.querySelectorAll('input[name="sheet_names"]:checked')).map(el=>el.value);
    const data = new URLSearchParams({
        username: "<?= $_SESSION['username'] ?>",
        chatbot_name: document.getElementById('chatbot_name').value,
        chatbot_id: document.getElementById('chatbot_id').value,
        gemini_api_key: document.getElementById('gemini_api_key').value,
        gemini_model: document.getElementById('gemini_model').value,
        sheet_id: document.getElementById('sheet_id').value,
        service_account_json: document.getElementById('service_account_json').value
    });
    selectedSheets.forEach(s => data.append('selected_sheets', s));

    const res = await fetch(`${API_BASE}/save_chatbot`, { method:'POST', body:data });
    if(res.ok) alert("Chatbot saved!");
}

// Load saved chatbots
async function loadSavedChatbots() {
    const listDiv = document.getElementById('savedList');
    const res = await fetch(`${API_BASE}/list_chatbots`);
    const data = await res.json();
    listDiv.innerHTML = '';
    data.forEach(cb=>{
        const div = document.createElement('div');
        div.className = 'savedItem';
        div.textContent = cb.chatbot_name + ' (' + cb.id + ')';
        div.onclick = ()=>fillForm(cb);
        listDiv.appendChild(div);
    });
    listDiv.style.display = 'block';
}

// Fill form with saved chatbot
function fillForm(cb){
    document.getElementById('chatbot_name').value = cb.chatbot_name;
    document.getElementById('chatbot_id').value = cb.id;
    document.getElementById('gemini_api_key').value = cb.gemini_api_key;
    document.getElementById('gemini_model').value = cb.gemini_model;
    document.getElementById('sheet_id').value = cb.sheet_id;
    document.getElementById('service_account_json').value = cb.service_account_json;

    const selectedSheets = JSON.parse(cb.selected_sheets || "[]");
    const container = document.getElementById('sheetSelection');
    container.querySelectorAll('input[name="sheet_names"]').forEach(input=>{
        input.checked = selectedSheets.includes(input.value);
    });
}
<?php endif; ?>
</script>
</body>
</html>


