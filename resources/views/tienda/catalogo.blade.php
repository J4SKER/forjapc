<x-app-layout :title="$categoria?->nombre ?? ($q !== '' ? 'Buscar: '.$q : 'Productos')">
    <livewire:tienda.catalogo :categoria="$categoria" :q="$q" />
</x-app-layout>
