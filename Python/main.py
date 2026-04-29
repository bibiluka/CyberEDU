import customtkinter as ctk
from tkinter import messagebox
import app_styles
import auth_logic

class CyberEDUApp(ctk.CTk):
    def __init__(self):
        super().__init__()

        self.title("CyberEDU - Application Desktop")
        self.geometry("1100x700")
        
        # Configuration du thème
        ctk.set_appearance_mode("light")
        
        self.current_user = None
        self.show_login_screen()

    def clear_screen(self):
        for widget in self.winfo_children():
            widget.destroy()

    def show_login_screen(self):
        self.clear_screen()
        self.configure(fg_color=app_styles.COLOR_HEADER)

        # Boîte de connexion centrale
        login_frame = ctk.CTkFrame(self, width=400, height=500, corner_radius=20, fg_color="white")
        login_frame.place(relx=0.5, rely=0.5, anchor="center")

        ctk.CTkLabel(login_frame, text="CyberEDU", font=app_styles.FONT_TITLE, text_color=app_styles.COLOR_HEADER).pack(pady=(40, 20))
        
        self.email_input = ctk.CTkEntry(login_frame, placeholder_text="Email", width=280, height=45)
        self.email_input.pack(pady=10)

        self.pass_input = ctk.CTkEntry(login_frame, placeholder_text="Mot de passe", show="*", width=280, height=45)
        self.pass_input.pack(pady=10)

        ctk.CTkButton(self, text="Se connecter", command=self.handle_login, 
                      fg_color=app_styles.COLOR_ACCENT, width=280, height=45).place(relx=0.5, rely=0.7, anchor="center")

    def handle_login(self):
        email = self.email_input.get()
        password = self.pass_input.get()
        user = auth_logic.verify_login(email, password)
        
        if user:
            self.current_user = user
            self.show_main_interface()
        else:
            messagebox.showerror("Erreur", "Identifiants incorrects")

    def show_main_interface(self):
        self.clear_screen()
        # On remet le fond en gris (début du dégradé)
        self.configure(fg_color=app_styles.COLOR_GRADIENT_TOP)

        # --- HEADER ---
        header = ctk.CTkFrame(self, height=70, fg_color=app_styles.COLOR_HEADER, corner_radius=0)
        header.pack(side="top", fill="x")
        
        ctk.CTkLabel(header, text="CyberEDU", font=("Inter", 20, "bold"), text_color="white").pack(side="left", padx=30)
        
        # Barre de recherche à droite (réduite)
        search_bar = ctk.CTkEntry(header, placeholder_text="Rechercher...", width=200, corner_radius=20)
        search_bar.pack(side="right", padx=30, pady=15)

        # --- MAIN CONTENT AREA (Dégradé simulé) ---
        # En Python pur, le vrai dégradé nécessite un Canvas, ici on utilise le fond du Frame
        main_content = ctk.CTkFrame(self, fg_color="transparent") # Transparent pour voir le fond de l'app
        main_content.pack(expand=True, fill="both", padx=40, pady=40)

        # --- LE CARRÉ BLEU (Container de la liste) ---
        self.list_container = ctk.CTkFrame(main_content, fg_color=app_styles.COLOR_CARD_BG, 
                                           border_width=2, border_color="#dbeafe", corner_radius=20)
        self.list_container.pack(expand=True, fill="both")

        ctk.CTkLabel(self.list_container, text="Vos Applications", font=("Inter", 18, "bold"), text_color="#1e3a8a").pack(pady=20)
        
        # Simulation des tuiles d'applications
        grid_frame = ctk.CTkFrame(self.list_container, fg_color="transparent")
        grid_frame.pack(pady=20, padx=20, fill="both", expand=True)

        apps = ["Cantine", "Messagerie", "Dashboard", "Profil"]
        for i, app_name in enumerate(apps):
            btn = ctk.CTkButton(grid_frame, text=app_name, width=150, height=100, corner_radius=10)
            btn.grid(row=i//2, column=i%2, padx=20, pady=20)

    def logout(self):
        self.current_user = None
        self.show_login_screen()

if __name__ == "__main__":
    app = CyberEDUApp()
    app.mainloop()