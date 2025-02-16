@extends('layouts.app')
@section('content')
    <div class="container mt-5">
        <div class="card shadow-lg">
            <div class="card-header bg-primary text-white text-center">
                <h4 class="mb-0">📂 Import Contacts via XML</h4>
            </div>
            <div class="card-body">
                <form id="uploadForm" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label for="xmlFile" class="form-label fw-bold">Choose XML File</label>
                        <input type="file" class="form-control" id="xmlFile" name="xml_file" accept=".xml" required>
                        <small class="text-danger" id="fileError"></small>
                    </div>
                    <button type="submit" class="btn btn-success w-100">📤 Upload & Import</button>
                </form>
            </div>
        </div>

        <div class="mt-4" id="errorTable" style="display:none;">
            <h5 class="text-danger">⚠ Validation Errors</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-hover text-center">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Last Name</th>
                            <th>Phone</th>
                            <th>Error</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div class="card shadow-lg mt-4">
            <div class="card-header bg-dark text-white text-center">
                <h4 class="mb-0">📋 Contact List</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="contactsTable" class="table table-striped text-center">
                        <thead class="table-primary">
                            <tr>
                                <th>ID</th>
                                <th>Full Name</th>
                                <th>Phone</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content shadow">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="editModalLabel">✏ Edit Contact</h5>
                        <button type="button" class="btn-close text-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="updateContactForm">
                            @csrf
                            <input type="hidden" id="editId">

                            <div class="mb-3">
                                <label for="editFirstName" class="form-label fw-bold">First Name</label>
                                <input type="text" class="form-control" id="editFirstName" name="name" required>
                                <small class="error-message text-danger"></small>
                            </div>

                            <div class="mb-3">
                                <label for="editLastName" class="form-label fw-bold">Last Name</label>
                                <input type="text" class="form-control" id="editLastName" name="last_name" required>
                                <small class="error-message text-danger"></small>
                            </div>

                            <div class="mb-3">
                                <label for="editPhone" class="form-label fw-bold">Phone</label>
                                <input type="text" class="form-control" id="editPhone" name="phone" required>
                                <small class="error-message text-danger"></small>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">💾 Update Contact</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script>
        $(document).ready(function() {

            let table = $('#contactsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: function(data, callback) {
                    let page = data.start / data.length + 1;
                    let perPage = data.length;
                    let searchValue = data.search.value;

                    $.ajax({
                        url: "{{ route('contacts.list') }}",
                        type: "GET",
                        data: {
                            page: page,
                            per_page: perPage,
                            search: searchValue
                        },
                        success: function(response) {
                            callback({
                                draw: data.draw,
                                recordsTotal: response.total,
                                recordsFiltered: response.total,
                                data: response.data
                            });
                        },
                        error: function() {
                            toastr.error("Failed to load contacts.");
                        }
                    });
                },
                columns: [{
                        data: 'id'
                    },
                    {
                        data: 'full_name'
                    },
                    {
                        data: 'phone'
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            return `
                    <button class="btn btn-sm btn-warning edit-btn"
                        data-id="${row.id}"
                        data-name="${row.full_name}"
                        data-phone="${row.phone}">✏ Edit</button>
                    <button class="btn btn-sm btn-danger delete-btn"
                        data-id="${row.id}">🗑 Delete</button>
            `;
                        },
                        orderable: false
                    }
                ],
                lengthMenu: [
                    [10, 25, 50],
                    [10, 25, 50]
                ],
                pageLength: 10
            });

            $('#perPageSelect').change(function() {
                table.ajax.reload();
            });

            $('#uploadForm').on('submit', function(e) {
                e.preventDefault();
                var formData = new FormData(this);

                $.ajax({
                    url: "{{ route('contacts.import') }}",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        toastr.success(
                            `Imported: ${response.successCount}, Duplicates: ${response.duplicateCount}`
                        );
                        table.ajax.reload();
                    },
                    error: function() {
                        toastr.error("Error uploading XML file.");
                    }
                });
            });

            $('#contactsTable tbody').on('click', '.delete-btn', function() {
                let contactId = $(this).data('id');

                Swal.fire({
                    title: "Are you sure?",
                    text: "You won't be able to recover this!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#d33",
                    cancelButtonColor: "#3085d6",
                    confirmButtonText: "Yes, delete it!"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/contacts/${contactId}`,
                            type: "DELETE",
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                toastr.success(response.message);
                                $('#contactsTable').DataTable().ajax.reload();
                            },
                            error: function() {
                                toastr.error("Error deleting contact.");
                            }
                        });
                    }
                });
            });

            $('#contactsTable tbody').on('click', '.edit-btn', function() {
                let contactId = $(this).data('id');
                let fullName = $(this).data('name');
                let phone = $(this).data('phone');
                let names = fullName.split(" ");

                $('#editId').val(contactId);
                $('#editFirstName').val(names[0]);
                $('#editLastName').val(names.slice(1).join(" "));
                $('#editPhone').val(phone);

                $('#editModal').modal('show');
            });

            $('#updateContactForm').on('submit', function(e) {
                e.preventDefault();
                let contactId = $('#editId').val();
                let formData = $(this).serialize() + "&_token=" + $('meta[name="csrf-token"]').attr(
                    'content');
                $('.error-message').remove();
                $.ajax({
                    url: `/contacts/${contactId}`,
                    type: "PUT",
                    data: formData,
                    success: function(response) {
                        toastr.success(response.message);
                        $('#editModal').modal('hide');
                        $('#contactsTable').DataTable().ajax.reload();
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            for (let field in errors) {
                                let errorMessage = errors[field][0];
                                let inputField = $(`#updateContactForm [name="${field}"]`);
                                inputField.after(
                                    `<small class="error-message text-danger">${errorMessage}</small>`
                                );
                            }
                        } else {
                            toastr.error("Error updating contact.");
                        }
                    }
                });
            });

            $('#xmlFile').on('change', function(event) {
                let file = event.target.files[0];
                let fileError = $('#fileError');
                let errorTable = $('#errorTable');
                let errorTableBody = errorTable.find('tbody');
                let uploadButton = $('button[type="submit"]');

                fileError.text("");
                errorTableBody.empty();
                errorTable.hide();
                uploadButton.prop('disabled', true);

                if (!file) {
                    fileError.text("Please select an XML file.");
                    return;
                }


                let fileName = file.name;
                if (!fileName.toLowerCase().endsWith(".xml")) {
                    fileError.text("Invalid file format. Please upload an XML file.");
                    return;
                }

                let reader = new FileReader();
                reader.readAsText(file);
                reader.onload = function(e) {
                    let parser = new DOMParser();
                    let xmlDoc = parser.parseFromString(e.target.result, "text/xml");


                    if (xmlDoc.getElementsByTagName("parsererror").length > 0) {
                        fileError.text(
                            "Invalid XML format. Please upload a properly formatted XML file.");
                        return;
                    }

                    let contacts = xmlDoc.getElementsByTagName("contact");
                    let errors = [];

                    $(contacts).each(function(index, contact) {
                        let name = $(contact).find("name").text().trim();
                        let lastName = $(contact).find("lastName").text().trim();
                        let phone = $(contact).find("phone").text().trim();
                        let errorMessage = [];

                        if (!name) errorMessage.push("First name is required.");
                        if (name.length > 255) errorMessage.push(
                            "First name must be less than 255 characters.");
                        if (!lastName) errorMessage.push("Last name is required.");
                        if (lastName.length > 255) errorMessage.push(
                            "Last name must be less than 255 characters.");

                        let phonePattern = /^\+90 \d{3} \d{7}$/;
                        if (!phone) errorMessage.push("Phone number is required.");
                        else if (!phonePattern.test(phone)) errorMessage.push(
                            "Phone must be in +90 XXX XXXXXXX format.");

                        if (errorMessage.length > 0) {
                            errors.push({
                                index: index + 1,
                                name: name || '-',
                                lastName: lastName || '-',
                                phone: phone || '-',
                                error: errorMessage.join(" ")
                            });
                        }
                    });

                    if (errors.length > 0) {
                        errorTable.show();
                        uploadButton.prop('disabled', true);

                        errors.forEach(error => {
                            let row = `
                <tr>
                    <td>${error.index}</td>
                    <td>${error.name}</td>
                    <td>${error.lastName}</td>
                    <td>${error.phone}</td>
                    <td>${error.error}</td>
                </tr>
                `;
                            errorTableBody.append(row);
                        });

                        fileError.text("Fix the errors before uploading.");
                    } else {
                        fileError.text("");
                        errorTable.hide();
                        uploadButton.prop('disabled', false);
                    }
                };
            });


        });
    </script>
@endsection
