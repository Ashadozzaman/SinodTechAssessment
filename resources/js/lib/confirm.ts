import Swal from 'sweetalert2';

export async function confirmDelete(message = 'Are you sure you want to delete this item?'): Promise<boolean> {
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
    });

    return result.isConfirmed;
}
