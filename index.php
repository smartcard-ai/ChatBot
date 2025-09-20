<?php
session_start();
$API_BASE = "http://localhost:5001";
// $API_BASE = "https://chatbot-1-v3ij.onrender.com";

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
        #savedList { 
            border:1px solid #ccc; 
            padding:10px; 
            max-height:200px; 
            overflow-y:auto; 
            margin-bottom:10px; 
            position: absolute; 
            background: white; 
            width: 250px; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 1000;
            display: none;
        }
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
                            <label for="loginUsername">Username</label>
                            <input type="text" class="form-control" id="loginUsername" required>
                        </div>
                        <div class="mb-3">
                            <label for="loginPassword">Password</label>
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
                            <label for="signupUsername">Username</label>
                            <input type="text" class="form-control" id="signupUsername" required>
                        </div>
                        <div class="mb-3">
                            <label for="signupPassword">Password</label>
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

    <!-- Chatbot Configuration Form -->
    <form id="configForm">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="chatbot_name">Chatbot Name</label>
                <input type="text" class="form-control" id="chatbot_name" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="chatbot_id">Chatbot ID</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="chatbot_id" readonly>
                    <button type="button" class="btn btn-secondary" onclick="generateID()">Generate ID</button>
                </div>
            </div>
        </div>

        <div class="mb-3">
            <label for="gemini_api_key">Gemini API Key</label>
            <input type="text" class="form-control" id="gemini_api_key" required>
        </div>

        <div class="mb-3">
            <label for="gemini_model">Gemini Model</label>
            <input type="text" class="form-control" id="gemini_model" value="gemini-2.0-flash" required>
        </div>

        <div class="mb-3">
            <label for="data_source">Data Source</label>
            <select class="form-select" id="data_source" onchange="onDataSourceChange()">
                <option value="google_sheets" selected>Google Sheets</option>
                <option value="mysql">MySQL</option>
                <option value="postgresql">PostgreSQL</option>
                <option value="neo4j">Neo4j</option>
                <option value="mongodb">MongoDB</option>
                <option value="databricks">Databricks</option>
                <option value="supabase">Supabase</option>
            </select>
        </div>

        <div id="googleSheetsFields">
            <div class="mb-3">
                <label for="sheet_id">Google Spreadsheet ID</label>
                <input type="text" class="form-control" id="sheet_id">
            </div>

            <div class="mb-3">
                <label for="service_account_json">Service Account JSON</label>
                <textarea class="form-control" id="service_account_json" rows="5"></textarea>
            </div>
        </div>

        <div id="dbFields" style="display:none;">
            <div class="mb-3">
                <label for="db_host">Database Host</label>
                <input type="text" class="form-control" id="db_host">
            </div>
            <div class="mb-3">
                <label for="db_port">Database Port</label>
                <input type="number" class="form-control" id="db_port" value="3306">
            </div>
            <div class="mb-3">
                <label for="db_name">Database Name</label>
                <input type="text" class="form-control" id="db_name">
            </div>
            <div class="mb-3">
                <label for="db_username">Database Username</label>
                <input type="text" class="form-control" id="db_username">
            </div>
            <div class="mb-3">
                <label for="db_password">Database Password</label>
                <input type="password" class="form-control" id="db_password">
            </div>
        </div>

        <div id="neo4jFields" style="display:none;">
            <div class="mb-3">
                <label for="neo4j_uri">Neo4j URI</label>
                <input type="text" class="form-control" id="neo4j_uri">
            </div>
            <div class="mb-3">
                <label for="neo4j_db_name">Database Name</label>
                <input type="text" class="form-control" id="neo4j_db_name">
            </div>
            <div class="mb-3">
                <label for="neo4j_username">Neo4j Username</label>
                <input type="text" class="form-control" id="neo4j_username">
            </div>
            <div class="mb-3">
                <label for="neo4j_password">Neo4j Password</label>
                <input type="password" class="form-control" id="neo4j_password">
            </div>
        </div>

        <div id="mongodbFields" style="display:none;">
            <div class="mb-3">
                <label for="mongo_uri">MongoDB URI</label>
                <input type="text" class="form-control" id="mongo_uri" placeholder="mongodb://localhost:27017">
            </div>
            <div class="mb-3">
                <label for="mongo_db_name">Database Name</label>
                <input type="text" class="form-control" id="mongo_db_name">
            </div>
        </div>

        <div id="databricksFields" style="display:none;">
            <div class="mb-3">
                <label for="databricks_host">Databricks Host</label>
                <input type="text" class="form-control" id="databricks_host" placeholder="https://your-databricks-workspace.cloud.databricks.com">
            </div>
            <div class="mb-3">
                <label for="databricks_token">Access Token</label>
                <input type="password" class="form-control" id="databricks_token">
            </div>
            <div class="mb-3">
                <label for="databricks_cluster_id">Cluster ID</label>
                <input type="text" class="form-control" id="databricks_cluster_id">
            </div>
            <div class="mb-3">
                <label for="databricks_db_name">Database Name</label>
                <input type="text" class="form-control" id="databricks_db_name">
            </div>
        </div>

        <div id="supabaseFields" style="display:none;">
            <div class="mb-3">
                <label for="supabase_host">Supabase Postgres Host</label>
<input type="text" class="form-control" id="supabase_host" placeholder="db.<project>.supabase.com">
            </div>
            <div class="mb-3">
                <label for="supabase_port">Supabase Postgres Port</label>
                <input type="number" class="form-control" id="supabase_port" value="5432">
            </div>
            <div class="mb-3">
                <label for="supabase_database">Supabase Postgres Database</label>
                <input type="text" class="form-control" id="supabase_database" placeholder="postgres">
            </div>
            <div class="mb-3">
                <label for="supabase_user">Supabase Postgres User</label>
                <input type="text" class="form-control" id="supabase_user" placeholder="postgres">
            </div>
            <div class="mb-3">
                <label for="supabase_password">Supabase Postgres Password</label>
                <input type="password" class="form-control" id="supabase_password" placeholder="Your database password">
            </div>
            <div class="mb-3">
                <label for="supabase_api_key">Supabase API Key</label>
                <input type="password" class="form-control" id="supabase_api_key">
            </div>
        </div>

        <button type="button" class="btn btn-primary mb-3" onclick="connectSpreadsheet()">Connect</button>
    </form>

    <!-- Sheet/Table Selection Form -->
    <form id="selectionForm">
        <div id="sheetSelection" class="mt-3"></div>
        <button type="button" class="btn btn-success mt-2" id="loadChatBtn" style="display:none;" onclick="loadChat()">Load to Chat</button>
    </form>

    <div id="chatInterface" class="mt-3" style="display:none;">
        <h4>Chat Interface</h4>
        <div id="chat"></div>
        <div class="input-group mt-2">
            <input type="text" id="user_input" class="form-control" placeholder="Ask about your data...">
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

function onDataSourceChange() {
    const dataSource = document.getElementById('data_source').value;
    const googleFields = document.getElementById('googleSheetsFields');
    const dbFields = document.getElementById('dbFields');
    const neo4jFields = document.getElementById('neo4jFields');
    const mongodbFields = document.getElementById('mongodbFields');
    const databricksFields = document.getElementById('databricksFields');
    const supabaseFields = document.getElementById('supabaseFields');
    if (dataSource === 'google_sheets') {
        googleFields.style.display = 'block';
        dbFields.style.display = 'none';
        neo4jFields.style.display = 'none';
        mongodbFields.style.display = 'none';
        databricksFields.style.display = 'none';
        supabaseFields.style.display = 'none';
    } else if (dataSource === 'neo4j') {
        googleFields.style.display = 'none';
        dbFields.style.display = 'none';
        neo4jFields.style.display = 'block';
        mongodbFields.style.display = 'none';
        databricksFields.style.display = 'none';
        supabaseFields.style.display = 'none';
    } else if (dataSource === 'mongodb') {
        googleFields.style.display = 'none';
        dbFields.style.display = 'none';
        neo4jFields.style.display = 'none';
        mongodbFields.style.display = 'block';
        databricksFields.style.display = 'none';
        supabaseFields.style.display = 'none';
    } else if (dataSource === 'databricks') {
        googleFields.style.display = 'none';
        dbFields.style.display = 'none';
        neo4jFields.style.display = 'none';
        mongodbFields.style.display = 'none';
        databricksFields.style.display = 'block';
        supabaseFields.style.display = 'none';
    } else if (dataSource === 'supabase') {
        googleFields.style.display = 'none';
        dbFields.style.display = 'none';
        neo4jFields.style.display = 'none';
        mongodbFields.style.display = 'none';
        databricksFields.style.display = 'none';
        supabaseFields.style.display = 'block';
    } else {
        googleFields.style.display = 'none';
        dbFields.style.display = 'block';
        neo4jFields.style.display = 'none';
        mongodbFields.style.display = 'none';
        databricksFields.style.display = 'none';
        supabaseFields.style.display = 'none';
        if (dataSource === 'mysql') {
            document.getElementById('db_port').value = '3306';
        } else if (dataSource === 'postgresql') {
            document.getElementById('db_port').value = '5432';
        }
    }
}

// Connect and list items
async function connectSpreadsheet() {
    const dataSource = document.getElementById('data_source').value;

    // Frontend validation for Supabase required fields
    if (dataSource === 'supabase') {
        const supabaseHost = document.getElementById('supabase_host').value.trim();
        const supabaseDatabase = document.getElementById('supabase_database').value.trim();
        const supabaseUser = document.getElementById('supabase_user').value.trim();
        const supabasePassword = document.getElementById('supabase_password').value.trim();

        if (!supabaseHost || !supabaseDatabase || !supabaseUser || !supabasePassword) {
            alert("Please fill in all required Supabase fields: Host, Database, User, and Password.");
            return;
        }
    }

    const data = new URLSearchParams({
        username:"<?= $_SESSION['username'] ?>",
        chatbot_name: document.getElementById('chatbot_name').value.trim(),
        chatbot_id: document.getElementById('chatbot_id').value.trim(),
        gemini_api_key: document.getElementById('gemini_api_key').value.trim(),
        gemini_model: document.getElementById('gemini_model').value.trim(),
        data_source: dataSource
    });

    if (dataSource === 'google_sheets') {
        data.append('sheet_id', document.getElementById('sheet_id').value.trim());
        data.append('service_account_json', document.getElementById('service_account_json').value.trim());
    } else if (dataSource === 'neo4j') {
        data.append('neo4j_uri', document.getElementById('neo4j_uri').value.trim());
        data.append('neo4j_db_name', document.getElementById('neo4j_db_name').value.trim());
        data.append('neo4j_username', document.getElementById('neo4j_username').value.trim());
        data.append('neo4j_password', document.getElementById('neo4j_password').value.trim());
    } else if (dataSource === 'mongodb') {
        data.append('mongo_uri', document.getElementById('mongo_uri').value.trim());
        data.append('mongo_db_name', document.getElementById('mongo_db_name').value.trim());
    } else if (dataSource === 'databricks') {
        data.append('databricks_host', document.getElementById('databricks_host').value.trim());
        data.append('databricks_token', document.getElementById('databricks_token').value.trim());
        data.append('databricks_cluster_id', document.getElementById('databricks_cluster_id').value.trim());
        data.append('databricks_db_name', document.getElementById('databricks_db_name').value.trim());
    } else if (dataSource === 'supabase') {
        data.append('supabase_host', document.getElementById('supabase_host').value.trim());
        data.append('supabase_port', document.getElementById('supabase_port').value.trim());
        data.append('supabase_database', document.getElementById('supabase_database').value.trim());
        data.append('supabase_user', document.getElementById('supabase_user').value.trim());
        data.append('supabase_password', document.getElementById('supabase_password').value.trim());
        data.append('supabase_api_key', document.getElementById('supabase_api_key').value.trim());
    } else {
        data.append('db_host', document.getElementById('db_host').value.trim());
        data.append('db_port', document.getElementById('db_port').value.trim());
        data.append('db_name', document.getElementById('db_name').value.trim());
        data.append('db_username', document.getElementById('db_username').value.trim());
        data.append('db_password', document.getElementById('db_password').value.trim());
    }

    // Debug: Log data being sent
    console.log('Connecting with data:', Object.fromEntries(data));

    const res = await fetch(`${API_BASE}/set_credentials`, { method:'POST', body:data });
    if(!res.ok) {
        const error = await res.json();
        return alert("Failed to connect: " + (error.error || "Unknown error"));
    }

    const json = await res.json();
    const container = document.getElementById('sheetSelection');
    container.innerHTML = "";

    const itemType = json.type;
    const itemName = itemType === 'sheets' ? 'sheet_names' : 'table_names';

    json.items.forEach(name => {
        const div = document.createElement('div');
        div.innerHTML = `<input type="checkbox" name="${itemName}" value="${name}"> ${name}`;
        container.appendChild(div);
    });

    document.getElementById('loadChatBtn').style.display = 'inline-block';
}

// Load selected items to chat
function loadChat() {
    const dataSource = document.getElementById('data_source').value;
    const itemName = dataSource === 'google_sheets' ? 'sheet_names' : 'table_names';
    const selectedItems = Array.from(document.querySelectorAll(`input[name="${itemName}"]:checked`))
                                .map(el=>el.value);
    if(selectedItems.length === 0) {
        const itemType = dataSource === 'google_sheets' ? 'sheet' : 'table';
        return alert(`Select at least one ${itemType}`);
    }

    const data = new URLSearchParams();
    selectedItems.forEach(s => data.append('item_names', s));

    fetch(`${API_BASE}/set_items`, { method:'POST', body:data })
        .then(res => res.json())
        .then(json => {
            document.getElementById('chatInterface').style.display='block';
            const itemType = dataSource === 'google_sheets' ? 'Sheets' : 'Tables';
            alert(`${itemType} loaded! You can now chat.`);
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
    // Validation: Ensure chatbot_id is generated
    if (!document.getElementById('chatbot_id').value) {
        alert("Please generate a Chatbot ID first.");
        return;
    }

    const dataSource = document.getElementById('data_source').value;
    const itemName = dataSource === 'google_sheets' ? 'sheet_names' : 'table_names';
    const selectedItems = Array.from(document.querySelectorAll(`input[name="${itemName}"]:checked`)).map(el=>el.value);
    const data = new URLSearchParams({
        username: "<?= $_SESSION['username'] ?>",
        chatbot_name: document.getElementById('chatbot_name').value,
        chatbot_id: document.getElementById('chatbot_id').value,
        gemini_api_key: document.getElementById('gemini_api_key').value,
        gemini_model: document.getElementById('gemini_model').value,
        data_source: dataSource
    });

    if (dataSource === 'google_sheets') {
        data.append('sheet_id', document.getElementById('sheet_id').value);
        data.append('service_account_json', document.getElementById('service_account_json').value);
    } else if (dataSource === 'neo4j') {
        data.append('neo4j_uri', document.getElementById('neo4j_uri').value);
        data.append('neo4j_db_name', document.getElementById('neo4j_db_name').value);
        data.append('neo4j_username', document.getElementById('neo4j_username').value);
        data.append('neo4j_password', document.getElementById('neo4j_password').value);
    } else if (dataSource === 'mongodb') {
        data.append('mongo_uri', document.getElementById('mongo_uri').value);
        data.append('mongo_db_name', document.getElementById('mongo_db_name').value);
    } else if (dataSource === 'databricks') {
        data.append('databricks_host', document.getElementById('databricks_host').value);
        data.append('databricks_token', document.getElementById('databricks_token').value);
        data.append('databricks_cluster_id', document.getElementById('databricks_cluster_id').value);
        data.append('databricks_db_name', document.getElementById('databricks_db_name').value);
    } else if (dataSource === 'supabase') {
        data.append('supabase_host', document.getElementById('supabase_host').value);
        data.append('supabase_port', document.getElementById('supabase_port').value);
        data.append('supabase_database', document.getElementById('supabase_database').value);
        data.append('supabase_user', document.getElementById('supabase_user').value);
        data.append('supabase_password', document.getElementById('supabase_password').value);
        data.append('supabase_api_key', document.getElementById('supabase_api_key').value);
    } else {
        data.append('db_host', document.getElementById('db_host').value);
        data.append('db_port', document.getElementById('db_port').value);
        data.append('db_name', document.getElementById('db_name').value);
        data.append('db_username', document.getElementById('db_username').value);
        data.append('db_password', document.getElementById('db_password').value);
    }

    selectedItems.forEach(s => data.append('selected_items', s));

    // Logging: Print data being sent
    console.log('Saving chatbot with data:', Object.fromEntries(data));

    const res = await fetch(`${API_BASE}/save_chatbot`, { method:'POST', body:data });
    if(res.ok) {
        alert("Chatbot saved!");
    } else {
        // Error handling: Alert user if save fails
        const error = await res.json();
        alert("Failed to save chatbot: " + (error.message || "Unknown error"));
    }
}

async function loadSavedChatbots() {
    const listDiv = document.getElementById('savedList');
    const username = "<?= $_SESSION['username'] ?>";
    const res = await fetch(`${API_BASE}/list_chatbots?username=${encodeURIComponent(username)}`);
    const data = await res.json();
    listDiv.innerHTML = '';
    data.forEach(cb=>{
        const div = document.createElement('div');
        div.className = 'savedItem';
        div.textContent = cb.chatbot_name + ' (' + cb.id + ')';
        div.onclick = ()=>{
            fillForm(cb);
            listDiv.style.display = 'none'; // Auto close dropdown on selection
            document.removeEventListener('click', outsideClickListener);
        };
        listDiv.appendChild(div);
    });
    // Position the dropdown below the button
    const button = document.querySelector('button.btn-info');
    const rect = button.getBoundingClientRect();
    listDiv.style.position = 'absolute';
    listDiv.style.top = (rect.bottom + window.scrollY) + 'px';
    listDiv.style.left = (rect.left + window.scrollX) + 'px';
    listDiv.style.width = rect.width + 'px';

    listDiv.style.display = 'block';

    // Add click outside listener to close dropdown
    setTimeout(() => {
        document.addEventListener('click', outsideClickListener);
    }, 0);

    function outsideClickListener(event) {
        if (!listDiv.contains(event.target) && event.target !== button) {
            listDiv.style.display = 'none';
            document.removeEventListener('click', outsideClickListener);
        }
    }
}

// Fill form with saved chatbot
function fillForm(cb){
    document.getElementById('chatbot_name').value = cb.chatbot_name;
    document.getElementById('chatbot_id').value = cb.id;
    document.getElementById('gemini_api_key').value = cb.gemini_api_key;
    document.getElementById('gemini_model').value = cb.gemini_model;
    document.getElementById('data_source').value = cb.data_source || 'google_sheets';
    onDataSourceChange(); // Update fields visibility

    if (cb.data_source === 'google_sheets') {
        document.getElementById('sheet_id').value = cb.sheet_id;
        document.getElementById('service_account_json').value = cb.service_account_json;
    } else if (cb.data_source === 'neo4j') {
        document.getElementById('neo4j_uri').value = cb.db_host;
        document.getElementById('neo4j_db_name').value = cb.db_name;
        document.getElementById('neo4j_username').value = cb.db_username;
        document.getElementById('neo4j_password').value = cb.db_password;
    } else if (cb.data_source === 'mongodb') {
        document.getElementById('mongo_uri').value = cb.mongo_uri;
        document.getElementById('mongo_db_name').value = cb.mongo_db_name;
    } else if (cb.data_source === 'databricks') {
        document.getElementById('databricks_host').value = cb.databricks_host;
        document.getElementById('databricks_token').value = cb.databricks_token;
        document.getElementById('databricks_cluster_id').value = cb.databricks_cluster_id;
        document.getElementById('databricks_db_name').value = cb.databricks_db_name;
    } else if (cb.data_source === 'supabase') {
        document.getElementById('supabase_host').value = cb.supabase_host || '';
        document.getElementById('supabase_port').value = cb.supabase_port || '5432';
        document.getElementById('supabase_database').value = cb.supabase_database || '';
        document.getElementById('supabase_user').value = cb.supabase_user || '';
        document.getElementById('supabase_password').value = cb.supabase_password || '';
        document.getElementById('supabase_api_key').value = cb.supabase_api_key || '';
    } else {
        document.getElementById('db_host').value = cb.db_host;
        document.getElementById('db_port').value = cb.db_port;
        document.getElementById('db_name').value = cb.db_name;
        document.getElementById('db_username').value = cb.db_username;
        document.getElementById('db_password').value = cb.db_password;
    }

    const selectedItems = JSON.parse(cb.selected_items || "[]");
    const itemName = cb.data_source === 'google_sheets' ? 'sheet_names' : 'table_names';
    const container = document.getElementById('sheetSelection');
    container.querySelectorAll(`input[name="${itemName}"]`).forEach(input=>{
        input.checked = selectedItems.includes(input.value);
    });
}
<?php endif; ?>
</script>
</body>
</html>


