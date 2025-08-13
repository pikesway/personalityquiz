import React, { useState, useEffect } from 'react';
import { apiFetch } from '@wordpress/api-fetch';

function App() {
  const [quizData, setQuizData] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    // This gets the current post's ID from the URL.
    const params = new URLSearchParams(window.location.search);
    const postId = params.get('post');

    if (postId) {
      // Call our new custom API endpoint
      apiFetch({ path: `/core-quiz/v1/quiz/${postId}` })
        .then((data) => {
          setQuizData(data);
          setIsLoading(false);
        })
        .catch((err) => {
          setError(err.message);
          setIsLoading(false);
        });
    } else {
        // This is a new quiz that hasn't been saved yet.
        setIsLoading(false);
        setQuizData({
            title: '',
            results: [],
            questions: []
        });
    }
  }, []); // The empty array ensures this runs only once on component load

  if (isLoading) {
    return <div>Loading Quiz Data...</div>;
  }

  if (error) {
    return <div style={{ color: 'red' }}>Error: {error}</div>;
  }

  return (
    <div>
      <h1>Core Quiz Builder</h1>
      
      {/* We will build the UI components here */}
      
      <hr />
      <h2>Raw Quiz Data (for debugging):</h2>
      <pre style={{ background: '#eee', padding: '1rem', whiteSpace: 'pre-wrap', wordBreak: 'break-all' }}>
        {JSON.stringify(quizData, null, 2)}
      </pre>
    </div>
  );
}

export default App;