<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RedAmigos - Bienvenido</title>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
    --color-principal: #222222;
    --color-secundario: #357ABD;
    --color-fondo: #f5f5f5;
    --color-blanco: #ffffff;
    --color-texto: #333333;
    --color-bordes: #cccccc;
    --color-placeholder: #888888;
    --color-hover: #2a619c;
    --sombra-suave: 0px 2px 8px rgba(0, 0, 0, 0.1);
    --transicion: all 0.3s ease;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background: var(--color-fondo);
    color: var(--color-texto);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    line-height: 1.5;
}

header {
    background: var(--color-principal);
    color: var(--color-blanco);
    padding: 20px;
    text-align: center;
}

header img {
    width: 100px;
    height: auto;
    margin-bottom: 10px;
}

header h1 {
    font-size: 1.8rem;
    margin-bottom: 5px;
}

.container {
    flex: 1;
    width: 90%;
    max-width: 420px;
    margin: 30px auto;
    padding: 25px;
    background: var(--color-blanco);
    border-radius: 12px;
    box-shadow: var(--sombra-suave);
}

h2 {
    color: var(--color-texto);
    text-align: center;
    margin-bottom: 20px;
    font-size: 1.4rem;
}

.welcome {
    text-align: center;
    font-size: 1rem;
    margin-bottom: 15px;
    color: #555;
}

.input-group {
    position: relative;
    margin-bottom: 20px;
}

.input-group input {
    width: 100%;
    padding: 14px 40px 14px 12px;
    border: 1px solid var(--color-bordes);
    border-radius: 8px;
    font-size: 1rem;
    transition: var(--transicion);
}

.input-group input:focus {
    outline: none;
    border-color: var(--color-secundario);
    box-shadow: 0 0 0 2px rgba(53, 122, 189, 0.2);
}

.input-group span {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 1.2rem;
    color: var(--color-placeholder);
    pointer-events: none;
}

button {
    background: var(--color-principal);
    color: var(--color-blanco);
    border: none;
    padding: 14px;
    width: 100%;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1.1rem;
    transition: var(--transicion);
    font-weight: 600;
}

button:hover, button:focus {
    background: var(--color-hover);
    outline: none;
}

.link {
    display: block;
    margin-top: 15px;
    text-align: center;
    color: var(--color-secundario);
    font-size: 1rem;
    text-decoration: none;
    transition: var(--transicion);
}

.link:hover, .link:focus {
    text-decoration: underline;
    color: var(--color-hover);
    outline: none;
}

.help {
    margin-top: 20px;
    text-align: center;
    font-size: 0.95rem;
    color: #777;
}

/* Estados de accesibilidad */
button:focus, .link:focus, input:focus {
    outline: 2px solid var(--color-secundario);
    outline-offset: 2px;
}

/* Responsive para dispositivos móviles pequeños */
@media (max-width: 480px) {
    .container {
        width: 95%;
        padding: 20px 15px;
        margin: 20px auto;
    }
    
    header {
        padding: 15px;
    }
    
    header h1 {
        font-size: 1.5rem;
    }
    
    h2 {
        font-size: 1.2rem;
    }
    
    .input-group input {
        padding: 12px 35px 12px 10px;
        font-size: 16px; /* Evita zoom en iOS */
    }
}

/* Responsive desktop */
@media (min-width: 768px) {
    header h1 {
        font-size: 2rem;
    }

    .container {
        max-width: 500px;
        padding: 35px;
    }

    h2 {
        font-size: 1.6rem;
    }
}
    </style>
   

</head>
