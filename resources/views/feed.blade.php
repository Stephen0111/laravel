
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Blog Feed</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">


  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">

  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f4f6f9;
    }

    .navbar-custom {
      background-color: #ffffff;
      box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    }

    .main-wrapper {
      max-width: 700px;
      margin: auto;
    }

    .make-post {
      background: #fff;
      border-radius: 16px;
      padding: 20px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }

    .blog-card {
      background: white;
      border-radius: 16px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      margin-bottom: 24px;
      overflow: hidden;
    }

    .blog-card img.post-image { /* Added .post-image to target specific img */
      width: 100%;
      height: auto;
      max-height: 300px;
      object-fit: cover;
    }

    .blog-card-body {
      padding: 16px;
    }

    .user-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
    }

    .form-control:focus {
      box-shadow: 0 0 0 0.25rem rgba(108, 99, 255, 0.2);
    }

    .center-btn {
      display: flex;
      justify-content: center;
    }

    .fw-semibold {
      font-weight: 600;
    }

    /* New styles for dynamic content */
    .poster-info {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
    }
    .poster-info .user-avatar { /* Ensure this applies to the poster's avatar */
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        margin-right: 10px;
    }
    .blog-tags span {
        display: inline-block;
        background-color: #e9ecef; /* Light gray background for tags */
        color: #495057;
        padding: 4px 10px;
        border-radius: 15px;
        font-size: 0.85em;
        margin-right: 5px;
        margin-top: 8px;
        text-transform: lowercase; /* Optional: make tags lowercase */
    }
    .reaction-count {
        font-size: 0.9em;
        color: #6c757d; /* Slightly darker gray for reactions */
        margin-top: 10px;
        display: block; /* Ensures it's on its own line */
    }
    /* Style for the action buttons container */
    .post-actions {
        display: flex;
        gap: 8px; /* Space between buttons */
    }
  </style>
</head>
<body>

  <nav class="navbar navbar-custom navbar-expand-lg px-4">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h5 class="fw-bold mb-0">MyBlog</h5>
      <img src="https://i.pravatar.cc/40" class="user-avatar" alt="User Profile">
    </div>
  </nav>

  <div class="container my-5 main-wrapper">
    <div class="make-post mb-4">
        <form id="new-post-form" method="POST" enctype="multipart/form-data">
          <div class="mb-3">
            <textarea class="form-control" id="post-content" rows="3" placeholder="What's on your mind?"></textarea>
          </div>
          <div class="mb-3">
            <input class="form-control" id="post-image" type="file" accept="image/*">
          </div>
          <div class="center-btn">
            <button type="submit" class="btn btn-primary px-4">Post</button>
          </div>
        </form>
    </div>

    <div id="blog-posts-container">
      <p class="text-center text-muted" id="loading-message">Loading blog posts...</p>
      <p class="text-center text-danger d-none" id="error-message">Failed to load posts. Please try again later.</p>
    </div>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    // Access the current user ID passed from Laravel
    // This variable will be 'null' if no user is logged in.
    const currentUserId = {{ $currentUserId ?? 'null' }};

    document.addEventListener('DOMContentLoaded', function() {
        const blogPostsContainer = document.getElementById('blog-posts-container');
        const loadingMessage = document.getElementById('loading-message');
        const errorMessage = document.getElementById('error-message');
        const newPostForm = document.getElementById('new-post-form');
        const postContentInput = document.getElementById('post-content');
        const postImageInput = document.getElementById('post-image');

        // Function to get CSRF token from meta tag
        function getCSRFToken() {
            const tokenMeta = document.querySelector('meta[name="csrf-token"]');
            return tokenMeta ? tokenMeta.getAttribute('content') : '';
        }

        // Function to render a single post card
        function renderPost(post, prepend = false) {
            const blogCard = document.createElement('div');
            blogCard.classList.add('blog-card');
            // Add a data attribute to easily select the post by its ID later for updates/deletion
            blogCard.setAttribute('data-post-id', post.id);

            // Determine image URL
            // If post.image_path is null (e.g., for external posts or local posts without image), use a placeholder
            const postImageUrl = post.image_path || `https://picsum.photos/seed/${post.id}/700/300`;

            // Check if the current user owns this post.
            // Note: 'external-' prefixed IDs from DummyJSON won't match internal user IDs.
            // Ensure comparison is done correctly, `==` for loose type matching if one is string and other int.


            const isOwner = currentUserId !== null && post.user_id == currentUserId;
            console.log('Current User ID:', currentUserId, 'Post User ID:', post.user_id, 'Is Owner:', isOwner);

            blogCard.innerHTML = `
                <img src="${postImageUrl}" class="post-image" alt="Post Image">
                <div class="blog-card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="poster-info">
                            <img src="${post.poster_image}" class="user-avatar" alt="${post.poster_name}'s avatar">
                            <div>
                                <h5 class="fw-semibold mb-0">${post.poster_name}</h5>
                                <small class="text-muted">Post ID: ${post.id}</small>
                            </div>
                        </div>
                        ${isOwner ? `
                            <div class="post-actions">
                                <button class="btn btn-sm btn-outline-primary edit-post-btn"
                                        data-post-id="${post.id}"
                                        data-post-content="${post.body.replace(/"/g, '&quot;')}"
                                        data-post-title="${post.title ? post.title.replace(/"/g, '&quot;') : ''}"
                                        data-image-url="${post.image_path || ''}">Edit</button>
                                <button class="btn btn-sm btn-outline-danger delete-post-btn"
                                        data-post-id="${post.id}">Delete</button>
                            </div>
                        ` : ''}
                    </div>
                    <h3 class="fw-semibold mb-2">${post.title || post.body.substring(0, 50) + '...' }</h3>
                    <p class="mb-2">${post.body}</p>
                    <div class="blog-tags">
                        ${post.tags && post.tags.length > 0 ? post.tags.map(tag => `<span>#${tag}</span>`).join('') : ''}
                    </div>
                    <span class="reaction-count">Reactions: ${post.reactions} ❤️</span>
                </div>
            `;
            if (prepend) {
                blogPostsContainer.prepend(blogCard); // Add to the top
            } else {
                blogPostsContainer.appendChild(blogCard); // Add to the bottom
            }

            // Attach event listeners ONLY if the current user owns the post AND it's a new element
            if (isOwner) {
                const editBtn = blogCard.querySelector('.edit-post-btn');
                const deleteBtn = blogCard.querySelector('.delete-post-btn');

                if (editBtn) {
                    editBtn.addEventListener('click', handleEditPost);
                }
                if (deleteBtn) {
                    deleteBtn.addEventListener('click', handleDeletePost);
                }
            }
        }

        // Initial fetch of blog posts
        // Changed URL to /blog-posts as per our web.php routes
        fetch('http://127.0.0.1:8001/api/blog-posts')
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                loadingMessage.classList.add('d-none');
                if (data.blogPosts && data.blogPosts.length > 0) {
                    data.blogPosts.forEach(post => {
                        renderPost(post); // Render existing posts
                    });
                } else {
                    blogPostsContainer.innerHTML = '<p class="text-center text-muted">No blog posts found.</p>';
                }
            })
            .catch(error => {
                loadingMessage.classList.add('d-none');
                errorMessage.classList.remove('d-none');
                console.error('Error fetching blog posts:', error);
            });

        // Handle new post form submission
        if (newPostForm) {
            newPostForm.addEventListener('submit', async function(e) {
                e.preventDefault(); // Prevent default form submission

                const content = postContentInput.value.trim();
                const imageFile = postImageInput.files[0]; // Get the selected file

                if (!content && !imageFile) {
                    alert('Please enter some content or select an image to post.');
                    return;
                }

                const formData = new FormData();
                formData.append('content', content);
                if (imageFile) {
                    formData.append('image', imageFile);
                }

                try {
                    // Changed URL to /blog-posts as per our web.php routes
                    const response = await fetch('http://127.0.0.1:8001/blog-posts', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': getCSRFToken()
                        },
                        body: formData, // FormData automatically sets 'Content-Type': 'multipart/form-data'
                    });

                    if (!response.ok) {
                        const errorData = await response.json();
                        throw new Error(errorData.message || `HTTP error! Status: ${response.status}`);
                    }

                    const result = await response.json();
                    alert(result.message); // Show success message

                    // Add the new post to the top of the timeline
                    renderPost(result.post, true);

                    // Clear the form
                    postContentInput.value = '';
                    postImageInput.value = ''; // Clear selected file
                } catch (error) {
                    alert('Error creating post: ' + error.message);
                    console.error('Error creating post:', error);
                }
            });
        }

        // --- Functions for Edit and Delete ---

        async function handleEditPost(event) {
            const postId = event.target.dataset.postId;
            const currentContent = event.target.dataset.postContent;
            // You can get the current image URL if you want to display it in the edit prompt
            // const currentImageUrl = event.target.dataset.imageUrl;

            // Using prompt for simplicity. For a better UX, use a Bootstrap Modal.
            let newContent = prompt('Edit your post content:', currentContent);
            if (newContent === null || newContent.trim() === '') {
                alert('Post content cannot be empty.');
                return;
            }

            // If you want to allow image editing, you'd need a more complex modal
            // with a file input and potentially a checkbox to remove the existing image.
            // For now, this example focuses on text content.

            const formData = new FormData();
            formData.append('content', newContent);
            // Laravel expects a _method field for PUT/PATCH requests when using FormData
            formData.append('_method', 'PUT'); // or 'PATCH'

            try {
                const response = await fetch(`http://127.0.0.1:8001/blog-posts/${postId}`, {
                    method: 'POST', // Fetch API sends POST if 'body' is FormData, so we use _method
                    headers: {
                        'X-CSRF-TOKEN': getCSRFToken()
                    },
                    body: formData,
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.message || `HTTP error! Status: ${response.status}`);
                }

                const result = await response.json();
                alert(result.message);

                // Find the updated post element in the DOM and update its content
                const updatedPostElement = document.querySelector(`.blog-card[data-post-id="${postId}"]`);
                if (updatedPostElement) {
                    // Update content paragraph
                    const contentParagraph = updatedPostElement.querySelector('p.mb-2');
                    if (contentParagraph) {
                        contentParagraph.textContent = result.post.body;
                    }
                    // Update title heading
                    const titleHeading = updatedPostElement.querySelector('h3.fw-semibold');
                    if (titleHeading) {
                        titleHeading.textContent = result.post.title || result.post.body.substring(0, 50) + '...';
                    }
                    // Update image if it changed (e.g., if you added image editing functionality)
                    const postImage = updatedPostElement.querySelector('img.post-image');
                    if (postImage && result.post.image_path) {
                        postImage.src = result.post.image_path;
                    } else if (postImage && !result.post.image_path) {
                        // If image was removed or no new image, revert to a generic placeholder
                        postImage.src = `https://picsum.photos/seed/${result.post.id}/700/300`;
                    }
                    // Update the data-post-content attribute for subsequent edits
                    event.target.dataset.postContent = result.post.body;
                    if (result.post.title) {
                        event.target.dataset.postTitle = result.post.title;
                    }
                    if (result.post.image_path) {
                         event.target.dataset.imageUrl = result.post.image_path;
                    } else {
                         event.target.dataset.imageUrl = '';
                    }
                }

            } catch (error) {
                alert('Error updating post: ' + error.message);
                console.error('Error updating post:', error);
            }
        }

        async function handleDeletePost(event) {
            const postId = event.target.dataset.postId;
            if (!confirm('Are you sure you want to delete this post? This cannot be undone.')) {
                return; // User cancelled
            }

            try {
                const response = await fetch(`http://127.0.0.1:8001/blog-posts/${postId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': getCSRFToken()
                    }
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.message || `HTTP error! Status: ${response.status}`);
                }

                const result = await response.json();
                alert(result.message);

                // Remove the post card from the DOM
                const deletedPostElement = document.querySelector(`.blog-card[data-post-id="${postId}"]`);
                if (deletedPostElement) {
                    deletedPostElement.remove();
                }

            } catch (error) {
                alert('Error deleting post: ' + error.message);
                console.error('Error deleting post:', error);
            }
        }
    });
  </script>
</body>
</html>
