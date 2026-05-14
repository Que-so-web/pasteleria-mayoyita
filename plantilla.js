
const buscarParam = new URLSearchParams(window.location.search);

const tipo = buscarParam.get("tipo");

const titulo = document.getElementById("titulo");

const display = document.getElementById("display");



const producto = {

    pan: [
        {
        nombre: "Panquecillos",
        descripcion: "Suave y esponjoso ",
        imagen: "imagenes/bollitos.jpeg"

        },

         {
        nombre: "Conchas",
        descripcion: "Suave y esponjoso ",
        imagen: "imagenes/conchas.jpeg"

        },

         {
        nombre: "Panque con chispas de chocolate",
        descripcion: "Suave y esponjoso ",
        imagen: "imagenes/panChispas.jpeg"

        }

        
    ],

    galletas: [
        {
        nombre: "Galletas",
        descripcion: "Crocantes y deliciosas ",
        imagen: "imagenes/galletas.jpeg"
    }

    ],

    pasteles: [{
        nombre: "Pasteles",
        descripcion: "Ricos y deliciosos ",
        imagen: "imagenes/pasteles.jpeg",
        descripcion: "Suave y esponjoso ",
        imagen: "imagenes/IMG-20260326-WA0068.jpg "
    }

    ],

    pasteles: [{
        nombre: "Pan",
        descripcion: "Suave y esponjoso ",
        imagen: "imagenes/IMG-20260326-WA0068.jpg "
    }
    ]};

 

if(producto[tipo]){

    titulo.innerText = tipo.toUpperCase();

       producto[tipo].forEach(productoA => {

        const espacio = document.createElement("div");

        espacio.classList.add("productoA-espacio");

        espacio.innerHTML = `
        <img src="${productoA.imagen}">

        <div class = "productoA-informacion">

        <h2>${productoA.nombre}</h2>

        <p>${productoA.descripcion}</p>

        <button> Me interesa </button>

        </div>
        `;
        display.appendChild(espacio);


        
    });

}else{
    titulo.innerText = "Categoria no encontrada";
}
