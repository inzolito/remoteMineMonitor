import csv
import shutil
from datetime import datetime
import pytz
from simple_salesforce import Salesforce

# Conectar a Salesforce
try:
    sf = Salesforce(
        username='paulina.aedo@hexagon.com',
        password='Hex$%$032918',
        security_token='oN9Oi28h5XdsE7OQ5DcDsmLu'
    )
except Exception as e:
    print(f"Error al conectar con Salesforce: {e}")
    exit(1)

# Leer la última fecha de consulta
try:
    with open('/mnt/c/laragon/www/Tickets/last_query_date.txt', 'r', encoding='utf-8') as f:
        last_query_date = f.read().strip()
except FileNotFoundError:
    last_query_date = '2024-01-01T00:00:00Z'

print("Última fecha de consulta:", last_query_date)

owner_names = [
    'Claudio Ponce', 'Guillermo Lamas', 'Gustavo Cheng', 'Maikol Salas',
    'Mauricio Estay', 'Nelson Donoso', 'Pablo Perez', 'Paulina Aedo',
    'Ricardo Rubio', 'Roberto Maldonado', 'Tomas Jimenez', 'Yazmin Sanchez',
    'Armando Mestanza', 'South American Support Q', 'Fernando Coronado'
]

owner_names_str = "', '".join(owner_names)

query = f"""
SELECT Id, CaseNumber, Status, Owner.Name, Account.Name, CreatedDate, Subject, ClosedDate, Description, Case_Resolution__c, Product_Name__c, Product_Family__c
FROM Case
WHERE Owner.Name IN ('{owner_names_str}')
AND CreatedDate >= 2024-01-01T00:00:00Z
"""
# Ejecutar la consulta en Salesforce
data = sf.query_all(query)

if not data['records']:
    print("No se encontraron registros en Salesforce.")
else:
    print(f"Se encontraron {len(data['records'])} registros.")

# Procesar los registros devueltos por Salesforce para tickets
chile_tz = pytz.timezone('Chile/Continental')
new_cases = {}
for record in data['records']:
    case_id = record.get('Id', 'N/A')
    case_number = record.get('CaseNumber', 'N/A')
    status = record.get('Status', 'N/A')
    owner_name = record['Owner']['Name'] if 'Owner' in record else 'N/A'
    account_name = record['Account']['Name'] if record.get('Account') else 'N/A'
    subject = record.get('Subject', 'N/A')
    closed_date = record.get('ClosedDate', 'N/A')
    description = record.get('Description', '')
    case_resolution = record.get('Case_Resolution__c', 'N/A')
    product_name = record.get('Product_Name__c', 'N/A')
    product_family = record.get('Product_Family__c', 'N/A')

    if description is not None:
        description = description.replace('\n', ' ').replace('\r', ' ').replace('"', '""')

    # Convertir CreatedDate
    if 'CreatedDate' in record:
        created_utc = datetime.strptime(record['CreatedDate'], "%Y-%m-%dT%H:%M:%S.%f+0000")
        created_utc = pytz.utc.localize(created_utc)
        created_date = created_utc.astimezone(chile_tz).strftime("%Y-%m-%d %H:%M:%S")
    else:
        created_date = 'N/A'

    # Convertir ClosedDate (si existe)
    if closed_date and closed_date != 'N/A':
        try:
            closed_utc = datetime.strptime(closed_date, "%Y-%m-%dT%H:%M:%S.%f+0000")
            closed_utc = pytz.utc.localize(closed_utc)
            closed_date = closed_utc.astimezone(chile_tz).strftime("%Y-%m-%d %H:%M:%S")
        except ValueError:
            print(f"Error al analizar la fecha de cierre para el caso {case_number}: {closed_date}")
            closed_date = 'N/A'
    else:
        closed_date = 'N/A'

    new_cases[case_number] = {
        'CaseId': case_id,
        'CaseNumber': case_number,
        'Status': status,
        'OwnerName': owner_name,
        'AccountName': account_name,
        'Subject': subject,
        'Description': description,
        'CaseResolution': case_resolution,
        'CreatedDate': created_date,
        'ClosedDate': closed_date,
        'ProductName': product_name,
        'ProductFamily': product_family
    }

# Guardar los registros de tickets en tickets.csv
with open('/home/jigsaw/msalas/script_salesforce/tickets.csv', 'w', newline='', encoding='utf-8-sig') as csvfile:
    fieldnames = ['CaseId', 'CaseNumber', 'Status', 'OwnerName', 'AccountName', 'Subject', 'Description', 'CaseResolution', 'CreatedDate', 'ClosedDate', 'ProductName', 'ProductFamily']
    writer = csv.DictWriter(csvfile, fieldnames=fieldnames)
    writer.writeheader()
    for record in new_cases.values():
        writer.writerow(record)

# Consultapara obtener los comentarios de trazabilidad
query_comments = f"""
SELECT ParentId, CreatedBy.Name, CreatedDate, CommentBody
FROM CaseComment
WHERE ParentId IN (SELECT Id FROM Case WHERE Owner.Name IN ('{owner_names_str}') AND CreatedDate >= 2025-01-01T00:00:00Z)
"""

# Ejecutar la consulta de comentarios en Salesforce
data_comments = sf.query_all(query_comments)

if not data_comments['records']:
    print("No se encontraron comentarios en Salesforce.")
else:
    print(f"Se encontraron {len(data_comments['records'])} comentarios.")
# Procesar los registros de comentarios para trazabilidad y agregar tanto el CaseNumber como el CaseId
comments_data = []

# Crear un diccionario para buscar comentarios por CaseId
comments_dict = {}
for record in data_comments['records']:
    parent_id = record.get('ParentId', '')
    comment_body = record.get('CommentBody', 'Sin comentarios').replace('\n', ' ').replace('\r', ' ').replace('"', '""')
    created_by = record.get('CreatedBy', {}).get('Name', 'N/A')
    created_date = record.get('CreatedDate', 'N/A')

    # Convertir CreatedDate a formato Chile/Continental
    if created_date != 'N/A':
        created_utc = datetime.strptime(created_date, "%Y-%m-%dT%H:%M:%S.%f+0000")
        created_utc = pytz.utc.localize(created_utc)
        created_date = created_utc.astimezone(pytz.timezone('Chile/Continental')).strftime("%Y-%m-%d %H:%M:%S")

    # Agregar comentario al diccionario por CaseId
    if parent_id in comments_dict:
        comments_dict[parent_id].append({
            'CreatedBy': created_by,
            'CreatedDate': created_date,
            'CommentBody': comment_body
        })
    else:
        comments_dict[parent_id] = [{
            'CreatedBy': created_by,
            'CreatedDate': created_date,
            'CommentBody': comment_body
        }]

# Asignar los comentarios a los casos correspondientes
for case_number, case_info in new_cases.items():
    case_id = case_info['CaseId']
    case_number = case_info['CaseNumber']
    case_created_date = case_info['CreatedDate']
    case_status = case_info ['Status']
    case_subject = case_info['Subject']
    case_owner = case_info['OwnerName']

    if case_id in comments_dict:
        for comment in comments_dict[case_id]:
            comments_data.append({
                'CaseId': case_id,
                'CaseNumber': case_number,
                'CaseCreatedDate': case_created_date,
                'Status': case_status,
                'Subject': case_subject,
                'OwnerName': case_owner,
                'CreatedBy': comment['CreatedBy'],
                'CreatedDate': comment['CreatedDate'],
                'CommentBody': comment['CommentBody']
            })
    else:
        comments_data.append({
            'CaseId': case_id,
            'CaseNumber': case_number,
            'CaseCreatedDate': case_created_date,
            'Status' : case_status,
            'Subject': case_subject,
            'OwnerName': case_owner,
            'CreatedBy': 'N/A',
            'CreatedDate': 'N/A',
            'CommentBody': 'Sin comentarios'
        })

# Guardar los registros de trazabilidad en trazabilidad.csv
with open('/home/jigsaw/msalas/script_salesforce/trazabilidad.csv', 'w', newline='', encoding='utf-8-sig') as csvfile:
    fieldnames = ['CaseId', 'CaseNumber', 'CaseCreatedDate', 'Status', 'Subject', 'OwnerName','CreatedBy', 'CreatedDate', 'CommentBody']
    writer = csv.DictWriter(csvfile, fieldnames=fieldnames)
    writer.writeheader()
    for record in comments_data:
        writer.writerow(record)

# Actualizar la fecha de la última consulta
new_query_date = datetime.utcnow().strftime('%Y-%m-%dT%H:%M:%SZ')
with open('/home/jigsaw/msalas/script_salesforce/last_query_date.txt', 'w', encoding='utf-8') as f:
    f.write(new_query_date)

print("Datos actualizados correctamente en tickets.csv y trazabilidad.csv.")

shutil.copy("/home/jigsaw/msalas/script_salesforce/tickets.csv", "/var/www/monitoreoLaboratorio/data/tickets.csv")
shutil.copy("/home/jigsaw/msalas/script_salesforce/trazabilidad.csv", "/var/www/monitoreoLaboratorio/data/trazabilidad.csv")
