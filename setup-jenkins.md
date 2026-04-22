---                                                                                                 
  1. Tambah .env sebagai Secret File di Jenkins                                                       
                                                                                                      
  Manage Jenkins → Credentials → Global → Add Credentials                                             
  - Kind: Secret file                                                                                 
  - ID: jp-doc-env                                                                                  
  - File: upload file .env yang sudah diisi (isi DB_HOST, DB_PASSWORD, APP_KEY, dll untuk production) 
                                                                                                      
  ---                                                                                                 
  2. Buat Pipeline Project di Jenkins                                                                 
                                                                                                      
  New Item → nama: jp-document-tracking → pilih Pipeline → OK                                       
                                                                                                      
  Di bagian Pipeline:                                                                                 
  - Definition: Pipeline script from SCM                                                              
  - SCM: Git                                                                                          
  - Repository URL: https://github.com/mdwisu/jp-document-tracking                                  
  - Branch: */main
  - Script Path: Jenkinsfile                                                                          
   
  ---                                                                                                 
  3. Pastikan di server Windows:                                                                    
  - Folder D:\projects\jp-document-tracking sudah ada dan IIS site sudah pointing ke sana             
  - App pool IIS punya nama yang sama dengan APP_SITE di Jenkinsfile                     
  - User yang menjalankan Jenkins service punya write permission ke D:\projects\                      
                                                                                                      
  ---                                                                                                 
  Satu hal yang perlu dikonfirmasi — nama app pool IIS-nya apa? Bisa cek di IIS Manager → Application 
  Pools.                 