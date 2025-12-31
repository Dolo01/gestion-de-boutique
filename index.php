<?php
session_start();
// ================= CONFIG DATABASE =================
$host = "localhost";
$db   = "gestion_boutique";
$user = "root";
$pass = "";
$pdo = new PDO(
    "mysql:host=$host;dbname=$db;charset=utf8",
    $user,
    $pass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// ================= LOGIN =================
if (isset($_POST['login'])) {
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE username=? AND password=MD5(?)");
    $stmt->execute([$_POST['username'], $_POST['password']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($admin) {
        $_SESSION['admin'] = $admin['username'];
    } else {
        $error = "Utilisateur ou mot de passe incorrect";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// ================= API =================
if (isset($_GET['api'])) {
    header("Content-Type: application/json");
    header("Access-Control-Allow-Origin: *");

    // -------- PRODUITS --------
    if ($_GET['api'] === 'produits') {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            echo json_encode($pdo->query("SELECT * FROM produits")->fetchAll(PDO::FETCH_ASSOC));
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $d = json_decode(file_get_contents("php://input"), true);
            $stmt = $pdo->prepare("INSERT INTO produits (nom, categorie, prix, stock) VALUES (?,?,?,?)");
            $stmt->execute([$d['nom'],$d['categorie'],$d['prix'],$d['stock']]);
            echo json_encode(["success"=>true]);
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            $pdo->prepare("DELETE FROM produits WHERE id=?")->execute([$_GET['id']]);
            echo json_encode(["success"=>true]);
            exit;
        }
    }

    // -------- VENTES --------
    if ($_GET['api']==='ventes') {
        if ($_SERVER['REQUEST_METHOD']==='GET') {
            echo json_encode($pdo->query("SELECT * FROM ventes ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC));
            exit;
        }
        if ($_SERVER['REQUEST_METHOD']==='POST') {
            $d=json_decode(file_get_contents("php://input"),true);
            $p=$pdo->prepare("SELECT * FROM produits WHERE id=?");
            $p->execute([$d['produit_id']]);
            $produit=$p->fetch(PDO::FETCH_ASSOC);
            if (!$produit || $produit['stock']<$d['quantite']) {
                http_response_code(400);
                echo json_encode(["error"=>"Stock insuffisant"]);
                exit;
            }
            $total=$produit['prix']*$d['quantite'];
            $pdo->prepare("INSERT INTO ventes (produit,categorie,prix,quantite,total) VALUES (?,?,?,?,?)")
                ->execute([$produit['nom'],$produit['categorie'],$produit['prix'],$d['quantite'],$total]);
            $pdo->prepare("UPDATE produits SET stock=stock-?,ventes=ventes+? WHERE id=?")
                ->execute([$d['quantite'],$d['quantite'],$d['produit_id']]);
            echo json_encode(["success"=>true]);
            exit;
        }
    }
}

if(!isset($_SESSION['admin'])):
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
<div class="bg-white p-6 rounded shadow w-full max-w-sm">
<h2 class="text-xl font-bold mb-4">Connexion Admin</h2>
<?php if(isset($error)) echo "<p class='text-red-600'>$error</p>"; ?>
<form method="POST">
<input name="username" placeholder="Utilisateur" class="border p-2 w-full mb-2">
<input name="password" type="password" placeholder="Mot de passe" class="border p-2 w-full mb-4">
<button type="submit" name="login" class="bg-blue-600 text-white px-4 py-2 w-full rounded">Connexion</button>
</form>
</div>
</body>
</html>
<?php
exit;
endif;
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-4">
<h1 class="text-2xl font-bold mb-4">Dashboard Admin</h1>
<a href="?logout=1" class="text-red-600 mb-4 inline-block">Déconnexion</a>
<div class="grid grid-cols-4 gap-4 mb-6">
<div class="bg-blue-500 text-white p-4 rounded shadow">Total Produits<p id="dProduits" class="text-2xl font-bold">0</p></div>
<div class="bg-green-500 text-white p-4 rounded shadow">Chiffre d'affaires<p id="dVentes" class="text-2xl font-bold">0 FCFA</p></div>
<div class="bg-purple-500 text-white p-4 rounded shadow">Stock Total<p id="dStock" class="text-2xl font-bold">0</p></div>
<div class="bg-yellow-500 text-white p-4 rounded shadow">Vente Journalière<p id="dVenteJour" class="text-2xl font-bold">0 FCFA</p></div>
</div>

<div class="grid grid-cols-2 gap-4">
<div class="bg-white p-4 rounded shadow">
<h2 class="text-xl font-bold mb-2">Produits</h2>
<div class="grid grid-cols-4 gap-2 mb-2">
<input id="nom" placeholder="Nom" class="border p-2">
<input id="cat" placeholder="Catégorie" class="border p-2">
<input id="prix" type="number" placeholder="Prix" class="border p-2">
<input id="stock" type="number" placeholder="Stock" class="border p-2">
</div>
<button onclick="addProduit()" class="bg-blue-600 text-white px-4 py-2 rounded mb-4">Ajouter</button>
<table class="w-full bg-white">
<thead class="bg-gray-200"><tr><th>Nom</th><th>Catégorie</th><th>Prix</th><th>Stock</th><th></th></tr></thead>
<tbody id="tProduits"></tbody>
</table>
</div>

<div class="bg-white p-4 rounded shadow">
<h2 class="text-xl font-bold mb-2">Ventes</h2>
<select id="prodSelect" class="border p-2 mb-2 w-full"></select>
<input id="qte" type="number" value="1" class="border p-2 mb-2 w-full">
<button onclick="vendre()" class="bg-green-600 text-white px-4 py-2 rounded mb-4">Vendre</button>
<table class="w-full bg-white">
<thead class="bg-gray-200"><tr><th>Produit</th><th>Qté</th><th>Total</th><th>Date</th></tr></thead>
<tbody id="tVentes"></tbody>
</table>
</div>
</div>

<script>
function load(){
fetch('?api=produits').then(r=>r.json()).then(d=>{
tProduits.innerHTML='';prodSelect.innerHTML='';let totalStock=0;
d.forEach(p=>{
prodSelect.innerHTML+=`<option value="${p.id}">${p.nom}</option>`;
tProduits.innerHTML+=`<tr><td>${p.nom}</td><td>${p.categorie}</td><td>${p.prix}</td><td>${p.stock}</td><td><button onclick="del(${p.id})" class="text-red-600">X</button></td></tr>`;
totalStock+=parseInt(p.stock);
});
dStock.innerText=totalStock;
dProduits.innerText=d.length;
});
fetch('?api=ventes').then(r=>r.json()).then(d=>{
tVentes.innerHTML='';let venteJour=0;let today=new Date().toLocaleDateString();
d.forEach(v=>{
tVentes.innerHTML+=`<tr><td>${v.produit}</td><td>${v.quantite}</td><td>${v.total}</td><td>${v.created_at}</td></tr>`;
if(new Date(v.created_at).toLocaleDateString()===today) venteJour+=parseInt(v.total);
});
dVenteJour.innerText=venteJour+' FCFA';
dVentes.innerText=d.reduce((sum,v)=>sum+parseInt(v.total),0)+' FCFA';
});
}
function addProduit(){fetch('?api=produits',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({nom:nom.value,categorie:cat.value,prix:prix.value,stock:stock.value})}).then(load);}
function vendre(){fetch('?api=ventes',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({produit_id:prodSelect.value,quantite:qte.value})}).then(load);}
function del(id){fetch('?api=produits&id='+id,{method:'DELETE'}).then(load);}
load();
</script>

</body>
</html>