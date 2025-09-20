import json
import pytest
from app import app  # Import the Flask app

# Test client
client = app.test_client()

# Databricks credentials from test.py
DATABRICKS_HOST = "dbc-9b509df0-b985.cloud.databricks.com"
DATABRICKS_HTTP_PATH = "/sql/1.0/warehouses/24bd5b21a32a2368"
DATABRICKS_TOKEN = "dapi132561f1eaed478132ebed70267c82b0"
DATABRICKS_DB_NAME = "default"  # Assuming a default db name, adjust if needed

def test_set_credentials_databricks():
    """Test /set_credentials with databricks data source"""
    data = {
        'data_source': 'databricks',
        'databricks_host': DATABRICKS_HOST,
        'databricks_cluster_id': DATABRICKS_HTTP_PATH,
        'databricks_token': DATABRICKS_TOKEN,
        'databricks_db_name': DATABRICKS_DB_NAME,
        'gemini_api_key': 'test_key',  # Mock key
        'gemini_model': 'gemini-1.5-flash'
    }
    response = client.post('/set_credentials', data=data)
    assert response.status_code == 200
    json_data = response.get_json()
    assert 'type' in json_data or 'error' in json_data
    if 'error' in json_data:
        print(f"Error in set_credentials: {json_data['error']}")
    else:
        print(f"Tables found: {json_data['items']}")

def test_set_items():
    """Test /set_items after setting credentials"""
    # First set credentials
    data = {
        'data_source': 'databricks',
        'databricks_host': DATABRICKS_HOST,
        'databricks_cluster_id': DATABRICKS_HTTP_PATH,
        'databricks_token': DATABRICKS_TOKEN,
        'databricks_db_name': DATABRICKS_DB_NAME,
        'gemini_api_key': 'test_key',
        'gemini_model': 'gemini-1.5-flash'
    }
    client.post('/set_credentials', data=data)

    # Now set items (assume some table names, e.g., 'test_table')
    response = client.post('/set_items', data={'item_names': ['test_table']})
    assert response.status_code == 200
    json_data = response.get_json()
    assert 'selected_items' in json_data

def test_chat():
    """Test /chat endpoint"""
    # Ensure credentials and items are set
    data = {
        'data_source': 'databricks',
        'databricks_host': DATABRICKS_HOST,
        'databricks_cluster_id': DATABRICKS_HTTP_PATH,
        'databricks_token': DATABRICKS_TOKEN,
        'databricks_db_name': DATABRICKS_DB_NAME,
        'gemini_api_key': 'AIzaSyB7MrpFiMoRM9O7S9DNS1gBCUWfxa0PMe4',
        'gemini_model': 'gemini-1.5-flash'
    }
    client.post('/set_credentials', data=data)
    client.post('/set_items', data={'item_names': ['test_table']})

    # Send chat message
    response = client.post('/chat', json={'message': 'Hello'})
    assert response.status_code == 200
    json_data = response.get_json()
    assert 'response' in json_data

def test_save_chatbot():
    """Test /save_chatbot with databricks config"""
    data = {
        'username': 'test_user',
        'chatbot_id': 'test_id',
        'chatbot_name': 'Test Chatbot',
        'gemini_api_key': 'test_key',
        'gemini_model': 'gemini-1.5-flash',
        'data_source': 'databricks',
        'databricks_host': DATABRICKS_HOST,
        'databricks_token': DATABRICKS_TOKEN,
        'databricks_cluster_id': DATABRICKS_HTTP_PATH,
        'databricks_db_name': DATABRICKS_DB_NAME,
        'selected_items': ['test_table']
    }
    response = client.post('/save_chatbot', data=data)
    assert response.status_code == 200
    json_data = response.get_json()
    assert json_data.get('success') == True

def test_list_chatbots():
    """Test /list_chatbots"""
    response = client.get('/list_chatbots?username=test_user')
    assert response.status_code == 200
    json_data = response.get_json()
    assert isinstance(json_data, list)

if __name__ == '__main__':
    pytest.main([__file__])
