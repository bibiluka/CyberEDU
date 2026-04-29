import mysql.connector
from tkinter import messagebox

def get_connection():
    try:
        conn = mysql.connector.connect(
            host="localhost",
            user="webmaster",
            password="Admin123",
            database="CyberEDU"
        )
        return conn
    except mysql.connector.Error as err:
        messagebox.showerror("Erreur BDD", f"Impossible de se connecter : {err}")
        return None