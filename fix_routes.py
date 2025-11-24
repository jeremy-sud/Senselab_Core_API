import re

file_path = 'routes/api.php'

with open(file_path, 'r') as f:
    content = f.read()

# Replacements
# permission:module.leer -> permission:ver-module
content = re.sub(r"permission:([\w_]+)\.leer", r"permission:ver-\1", content)

# permission:module.crear -> permission:crear-module
content = re.sub(r"permission:([\w_]+)\.crear", r"permission:crear-\1", content)

# permission:module.actualizar -> permission:editar-module
content = re.sub(r"permission:([\w_]+)\.actualizar", r"permission:editar-\1", content)

# permission:module.eliminar -> permission:eliminar-module
content = re.sub(r"permission:([\w_]+)\.eliminar", r"permission:eliminar-\1", content)

with open(file_path, 'w') as f:
    f.write(content)

print("Routes updated successfully.")
