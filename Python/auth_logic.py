from database import get_connection

def verify_login(email, password):
    db = get_connection()
    if not db: return None
    
    cursor = db.cursor(dictionary=True)
    # On utilise des requêtes préparées pour éviter les injections SQL
    query = "SELECT * FROM users WHERE email = %s AND password = %s"
    cursor.execute(query, (email, password))
    user = cursor.fetchone()
    db.close()
    return user

def register_user(email, password):
    db = get_connection()
    if not db: return False
    
    cursor = db.cursor()
    try:
        query = "INSERT INTO users (email, password) VALUES (%s, %s)"
        cursor.execute(query, (email, password))
        db.commit()
        return True
    except:
        return False
    finally:
        db.close()