<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Incidentes</title>

<style>

body{
    background:#0f172a;
    color:white;
    font-family:Arial;
    padding:40px;
}

h1{
    margin-bottom:30px;
}

table{
    width:100%;
    border-collapse:collapse;
}

th,td{
    border:1px solid #334155;
    padding:12px;
    text-align:left;
}

th{
    background:#1e293b;
}

td{
    background:#111827;
}

input,select{
    width:100%;
    padding:8px;
    background:#0f172a;
    color:white;
    border:1px solid #334155;
}

button{
    padding:8px 14px;
    border:none;
    cursor:pointer;
    color:white;
}

.editar{
    background:#0d9488;
}

.eliminar{
    background:#dc2626;
    margin-top:6px;
}

.estado{
    color:#22c55e;
    font-weight:bold;
}

.ir{
    color:#f97316;
    font-weight:bold;
}

</style>
</head>

<body>

<h1>Lista de Incidentes</h1>

<table>

<tr>
    <th>ID</th>
    <th>Tipo</th>
    <th>Descripción</th>
    <th>IR</th>
    <th>Estado</th>
    <th>Acciones</th>
</tr>

@foreach($eventos as $e)

<tr>

<td>{{ $e->id }}</td>

<td>

<form method="POST" action="/incidente/{{ $e->id }}">

@csrf
@method('PUT')

<select name="tipo">

<option value="inundacion" {{ $e->tipo == 'inundacion' ? 'selected' : '' }}>
Inundación
</option>

<option value="vendaval" {{ $e->tipo == 'vendaval' ? 'selected' : '' }}>
Vendaval
</option>

<option value="deslizamiento" {{ $e->tipo == 'deslizamiento' ? 'selected' : '' }}>
Deslizamiento
</option>

<option value="erosion_costera" {{ $e->tipo == 'erosion_costera' ? 'selected' : '' }}>
Erosión costera
</option>

<option value="sequia" {{ $e->tipo == 'sequia' ? 'selected' : '' }}>
Sequía
</option>

<option value="marejada" {{ $e->tipo == 'marejada' ? 'selected' : '' }}>
Marejada
</option>

<option value="incendio_forestal" {{ $e->tipo == 'incendio_forestal' ? 'selected' : '' }}>
Incendio forestal
</option>

<option value="sismo" {{ $e->tipo == 'sismo' ? 'selected' : '' }}>
Sismo
</option>

</select>

</td>

<td>

<input
type="text"
name="descripcion"
value="{{ $e->descripcion }}"
>

</td>

<td class="ir">
{{ $e->ir }}
</td>

<td class="estado">
{{ $e->estado }}
</td>

<td>

<button class="editar">
Actualizar
</button>

</form>

<form method="POST" action="/incidente/{{ $e->id }}">

@csrf
@method('DELETE')

<button class="eliminar">
Eliminar
</button>

</form>

</td>

</tr>

@endforeach

</table>

</body>
</html>